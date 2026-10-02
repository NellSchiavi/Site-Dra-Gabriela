<?php

namespace App\Services;

use App\Exceptions\AlteracaoDeAgendaAfetaConsultasException;
use App\Models\AdminLog;
use App\Models\Appointment;
use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use App\Models\ConfiguracaoGlobal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Orquestra alterações administrativas de disponibilidade: horário padrão
 * semanal e bloqueios (dia inteiro ou faixa de horário). Nenhuma regra de disponibilidade é recalculada aqui — este
 * serviço só grava configuração e usa o AvailabilityService (já existente)
 * como única fonte de verdade para decidir se uma consulta já agendada
 * ainda cabe no expediente resultante.
 *
 * Toda alteração segue o mesmo fluxo:
 *   1. abre transação e adquire o(s) lock(s) de agenda_locks (mesmo
 *      mecanismo do AppointmentService, em ordem cronológica consistente
 *      para não gerar deadlock com reservas públicas concorrentes);
 *   2. aplica a alteração tentativamente;
 *   3. pergunta ao AvailabilityService se alguma consulta ativa deixou de
 *      caber no expediente resultante;
 *   4. se houver afetadas e ainda não há confirmação explícita, lança
 *      AlteracaoDeAgendaAfetaConsultasException — a transação inteira é
 *      revertida (nada é persistido) e o controller mostra a confirmação;
 *   5. só com confirmação explícita: cancela as consultas afetadas (via
 *      AppointmentService::cancelar(), sem duplicar a regra de transição) e
 *      grava o AdminLog — tudo dentro da mesma transação.
 */
class AdminAvailabilityService
{
    private const MOTIVO_CANCELAMENTO = 'Cancelado por alteração administrativa da agenda.';

    public function __construct(
        private readonly AppointmentService $appointments,
        private readonly AvailabilityService $availability,
    ) {}

    /**
     * @param  array{hora_inicio: string, hora_fim: string, ativo: bool}  $dados
     * @return Collection<int, Appointment> consultas canceladas por esta operação
     *
     * @throws AlteracaoDeAgendaAfetaConsultasException
     */
    public function atualizarRegraSemanal(int $diaDaSemana, array $dados, User $admin, bool $confirmado): Collection
    {
        // Nem a data de hoje nem o horizonte de antecedência
        // dependem de nenhuma linha travada por esta operação — calculados
        // fora da transação, para que a PRIMEIRA leitura feita já DENTRO da
        // transação seja a aquisição do lock (evita decidir com base num
        // snapshot fixado antes do lock).
        $horizonte = ConfiguracaoGlobal::atual()->antecedencia_maxima_dias;
        $datas = $this->datasFuturasParaDiaDaSemana($diaDaSemana, $horizonte);

        $afetadas = DB::transaction(function () use ($diaDaSemana, $dados, $admin, $confirmado, $datas) {
            $this->travarDatasEmOrdem($datas);

            $regra = AvailabilityRule::query()->firstOrNew(['day_of_week' => $diaDaSemana]);
            $existiaAntes = $regra->exists;
            $estadoAnterior = $existiaAntes ? $regra->only(['hora_inicio', 'hora_fim', 'ativo']) : null;

            $regra->day_of_week = $diaDaSemana;
            $regra->hora_inicio = $dados['hora_inicio'];
            $regra->hora_fim = $dados['hora_fim'];
            $regra->ativo = $dados['ativo'];
            $regra->save();

            $afetadas = collect();
            foreach ($datas as $data) {
                $afetadas = $afetadas->merge($this->consultasQueNaoCabem($data));
            }

            $this->confirmarOuLancarConflito($afetadas, $confirmado);

            $this->cancelarConsultasAfetadas($afetadas);

            $this->registrarLog($admin, $existiaAntes ? 'disponibilidade.horario_padrao.atualizado' : 'disponibilidade.horario_padrao.criado', [
                'day_of_week' => $diaDaSemana,
                'estado_anterior' => $estadoAnterior,
                'novo_estado' => $regra->only(['hora_inicio', 'hora_fim', 'ativo']),
                'consultas_canceladas' => $afetadas->pluck('id')->all(),
            ]);

            return $afetadas;
        });

        return $afetadas;
    }

    /**
     * Cria ou atualiza uma exceção de horário (tipo "horario_especial") ou um
     * bloqueio de dia inteiro (tipo "fechado_total") — mesma tabela, mesmo
     * fluxo, só o "tipo" e os campos de horário mudam.
     *
     * @param  array{data: string, tipo: string, hora_inicio?: string|null, hora_fim?: string|null, motivo?: string|null}  $dados
     * @return Collection<int, Appointment>
     *
     * @throws AlteracaoDeAgendaAfetaConsultasException
     */
    public function salvarExcecao(?AvailabilityException $existente, array $dados, User $admin, bool $confirmado): Collection
    {
        $afetadas = DB::transaction(function () use ($existente, $dados, $admin, $confirmado) {
            $data = CarbonImmutable::parse($dados['data'])->startOfDay();

            $this->appointments->adquirirLockDoDia($data);

            $excecao = $existente ?? ($dados['tipo'] === AvailabilityException::TIPO_FECHADO_PARCIAL
                ? new AvailabilityException
                : AvailabilityException::query()->firstOrNew([
                    'data' => $data->toDateString(),
                    'tipo' => $dados['tipo'],
                ]));
            $existiaAntes = $excecao->exists;
            $estadoAnterior = $existiaAntes ? $excecao->only(['tipo', 'hora_inicio', 'hora_fim', 'motivo']) : null;

            $temFaixaDeHorario = $dados['tipo'] !== AvailabilityException::TIPO_FECHADO_TOTAL;

            $excecao->data = $data->toDateString();
            $excecao->tipo = $dados['tipo'];
            $excecao->hora_inicio = $temFaixaDeHorario ? $dados['hora_inicio'] : null;
            $excecao->hora_fim = $temFaixaDeHorario ? $dados['hora_fim'] : null;
            $excecao->motivo = $dados['motivo'] ?? null;
            $excecao->created_by ??= $admin->id;
            $excecao->save();

            $afetadas = $this->consultasQueNaoCabem($data);

            $this->confirmarOuLancarConflito($afetadas, $confirmado);

            $this->cancelarConsultasAfetadas($afetadas);

            $rotulo = $dados['tipo'] === AvailabilityException::TIPO_FECHADO_TOTAL ? 'bloqueio_dia' : 'bloqueio_horario';
            $acao = "disponibilidade.{$rotulo}." . ($existiaAntes ? 'atualizado' : 'criado');

            $this->registrarLog($admin, $acao, [
                'data' => $data->toDateString(),
                'estado_anterior' => $estadoAnterior,
                'novo_estado' => $excecao->only(['tipo', 'hora_inicio', 'hora_fim', 'motivo']),
                'consultas_canceladas' => $afetadas->pluck('id')->all(),
            ]);

            return $afetadas;
        });

        return $afetadas;
    }

    public function removerExcecao(AvailabilityException $excecao, User $admin): void
    {
        DB::transaction(function () use ($excecao, $admin) {
            $tipo = $excecao->tipo;
            $estadoAnterior = $excecao->only(['data', 'tipo', 'hora_inicio', 'hora_fim', 'motivo']);

            $excecao->delete();

            $rotulo = $tipo === AvailabilityException::TIPO_FECHADO_TOTAL ? 'bloqueio_dia' : 'bloqueio_horario';
            $this->registrarLog($admin, "disponibilidade.{$rotulo}.removido", ['estado_anterior' => $estadoAnterior]);
        });
    }

    /**
     * @throws AlteracaoDeAgendaAfetaConsultasException
     */
    private function confirmarOuLancarConflito(Collection $afetadas, bool $confirmado): void
    {
        if ($afetadas->isNotEmpty() && ! $confirmado) {
            // Lançar aqui dentro da transação força o rollback de tudo que já
            // foi feito nesta operação (item 7 — nunca estado parcial).
            throw new AlteracaoDeAgendaAfetaConsultasException($afetadas);
        }
    }

    private function cancelarConsultasAfetadas(Collection $afetadas): void
    {
        foreach ($afetadas as $consulta) {
            $this->appointments->cancelar($consulta, self::MOTIVO_CANCELAMENTO);
        }
    }

    /**
     * Consultas ativas de uma data que NÃO cabem inteiramente em nenhum dos
     * intervalos abertos do expediente resultante. Usa
     * AvailabilityService::intervalosAbertosDoDia() (regra+exceção
     * já resolvidos) — nunca a grade de slots (horariosDisponiveis), que
     * geraria falsos conflitos sempre que o horário de início mudasse.
     *
     * @return Collection<int, Appointment>
     */
    private function consultasQueNaoCabem(CarbonImmutable $data): Collection
    {
        $abertos = $this->availability->intervalosAbertosDoDia($data);

        return Appointment::query()
            ->ativos()
            ->naData($data)
            ->with('tratamento:id,nome')
            ->get()
            ->filter(function (Appointment $consulta) use ($abertos) {
                $inicio = $this->availability->paraMinutos($consulta->hora_inicio);
                $fim = $this->availability->paraMinutos($consulta->hora_fim);

                foreach ($abertos as [$aberturaInicio, $aberturaFim]) {
                    if ($inicio >= $aberturaInicio && $fim <= $aberturaFim) {
                        return false;
                    }
                }

                return true;
            })
            ->values();
    }

    /**
     * @return array<int, CarbonImmutable>
     */
    private function datasFuturasParaDiaDaSemana(int $diaDaSemana, int $horizonteDias): array
    {
        $hoje = CarbonImmutable::now()->startOfDay();
        $datas = [];

        for ($i = 0; $i <= $horizonteDias; $i++) {
            $candidata = $hoje->addDays($i);

            if ($candidata->dayOfWeek === $diaDaSemana) {
                $datas[] = $candidata;
            }
        }

        return $datas;
    }

    /**
     * Trava as datas em ordem cronológica antes de qualquer checagem — ordem
     * consistente (item 16) evita deadlock com o AppointmentService, que
     * também trava agenda_locks por data.
     *
     * @param  array<int, CarbonImmutable>  $datas
     */
    private function travarDatasEmOrdem(array $datas): void
    {
        usort($datas, fn (CarbonImmutable $a, CarbonImmutable $b) => $a <=> $b);

        foreach ($datas as $data) {
            $this->appointments->adquirirLockDoDia($data);
        }
    }

    private function registrarLog(User $admin, string $acao, array $detalhes): void
    {
        AdminLog::create([
            'user_id' => $admin->id,
            'acao' => $acao,
            'detalhes' => $detalhes,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Duração da consulta (em minutos) usada para fatiar os horários livres.
     * Consultas já agendadas mantêm seu próprio início/fim — a checagem de
     * sobreposição por intervalo continua valendo para elas.
     */
    public function atualizarDuracaoConsulta(int $minutos, User $admin): void
    {
        DB::transaction(function () use ($minutos, $admin) {
            $config = ConfiguracaoGlobal::atual();
            $anterior = $config->duracao_consulta_min;

            $config->duracao_consulta_min = $minutos;
            $config->save();

            $this->registrarLog($admin, 'disponibilidade.duracao_consulta.atualizada', [
                'anterior' => $anterior,
                'novo' => $minutos,
            ]);
        });
    }
}
