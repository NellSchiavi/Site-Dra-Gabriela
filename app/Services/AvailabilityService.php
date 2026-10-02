<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use App\Models\ConfiguracaoGlobal;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Calcula os horários realmente disponíveis para uma data (agenda de recurso
 * único — a Dra. atende todos os tratamentos), seguindo o pipeline:
 *
 *   horários padrão -> exceções/bloqueios -> agendamentos -> disponíveis
 *
 * É a única fonte de verdade da disponibilidade: tanto a consulta pública de
 * horários quanto a revalidação feita pelo AppointmentService no momento de
 * confirmar um agendamento passam por aqui — o frontend nunca decide sozinho
 * o que está disponível.
 */
class AvailabilityService
{
    /**
     * @return array<int, array{inicio: string, fim: string}> lista ordenada de slots disponíveis
     */
    public function horariosDisponiveis(int $tratamentoId, CarbonInterface $data, ?ConfiguracaoGlobal $config = null): array
    {
        $config ??= ConfiguracaoGlobal::atual();
        $data = CarbonImmutable::parse($data)->startOfDay();

        $intervalos = $this->intervalosBaseDoDia($data);
        $intervalos = $this->aplicarExcecoes($intervalos, $data);
        $intervalos = $this->removerHorariosOcupados($intervalos, $tratamentoId, $data);

        return $this->fatiarEmSlots($intervalos, $data, $config);
    }

    /**
     * Usado pelo AppointmentService para revalidar, no momento da confirmação,
     * se um slot específico ainda está entre os disponíveis.
     */
    public function estaDisponivel(int $tratamentoId, CarbonInterface $data, string $horaInicio, string $horaFim, ?ConfiguracaoGlobal $config = null): bool
    {
        foreach ($this->horariosDisponiveis($tratamentoId, $data, $config) as $slot) {
            if ($slot['inicio'] === $horaInicio && $slot['fim'] === $horaFim) {
                return true;
            }
        }

        return false;
    }

    /**
     * Intervalos "abertos" do expediente do dia — regra padrão e
     * exceções já resolvidos, mas ANTES de descontar os agendamentos
     * existentes. Usado pela camada administrativa para checar se
     * uma consulta já existente ainda cabe no expediente depois de uma
     * alteração de disponibilidade, sem duplicar a resolução de
     * regra/exceção que já vive aqui — só reaproveita os mesmos
     * passos internos que horariosDisponiveis() já usa antes de gerar slots.
     *
     * @return array<int, array{0: int, 1: int}> intervalos em minutos desde a meia-noite
     */
    public function intervalosAbertosDoDia(CarbonInterface $data): array
    {
        $data = CarbonImmutable::parse($data)->startOfDay();

        $intervalos = $this->intervalosBaseDoDia($data);

        return $this->aplicarExcecoes($intervalos, $data);
    }

    /**
     * @return array<int, array{0: int, 1: int}> intervalos em minutos desde a meia-noite
     */
    private function intervalosBaseDoDia(CarbonImmutable $data): array
    {
        return AvailabilityRule::query()
            ->ativo()
            ->paraDiaDaSemana($data->dayOfWeek)
            ->get()
            ->map(fn (AvailabilityRule $regra) => [
                $this->paraMinutos($regra->hora_inicio),
                $this->paraMinutos($regra->hora_fim),
            ])
            ->all();
    }

    /**
     * @param  array<int, array{0: int, 1: int}>  $intervalos
     * @return array<int, array{0: int, 1: int}>
     */
    private function aplicarExcecoes(array $intervalos, CarbonImmutable $data): array
    {
        $excecoes = AvailabilityException::query()->naData($data)->get();

        if ($excecoes->contains(fn (AvailabilityException $e) => $e->tipo === AvailabilityException::TIPO_FECHADO_TOTAL)) {
            return [];
        }

        $especiais = $excecoes->where('tipo', AvailabilityException::TIPO_HORARIO_ESPECIAL);
        if ($especiais->isNotEmpty()) {
            $intervalos = $especiais
                ->map(fn (AvailabilityException $e) => [
                    $this->paraMinutos($e->hora_inicio),
                    $this->paraMinutos($e->hora_fim),
                ])
                ->all();
        }

        foreach ($excecoes->where('tipo', AvailabilityException::TIPO_FECHADO_PARCIAL) as $bloqueio) {
            $intervalos = $this->subtrairIntervalo(
                $intervalos,
                $this->paraMinutos($bloqueio->hora_inicio),
                $this->paraMinutos($bloqueio->hora_fim)
            );
        }

        return $intervalos;
    }

    /**
     * @param  array<int, array{0: int, 1: int}>  $intervalos
     * @return array<int, array{0: int, 1: int}>
     */
    private function removerHorariosOcupados(array $intervalos, int $tratamentoId, CarbonImmutable $data): array
    {
        $ocupados = Appointment::query()
            ->ativos()
            ->naData($data)
            ->get(['hora_inicio', 'hora_fim']);

        foreach ($ocupados as $agendamento) {
            $intervalos = $this->subtrairIntervalo(
                $intervalos,
                $this->paraMinutos($agendamento->hora_inicio),
                $this->paraMinutos($agendamento->hora_fim)
            );
        }

        return $intervalos;
    }

    /**
     * Subtrai o intervalo [inicio, fim) de uma lista de intervalos abertos,
     * podendo dividir um intervalo existente em dois.
     *
     * @param  array<int, array{0: int, 1: int}>  $intervalos
     * @return array<int, array{0: int, 1: int}>
     */
    private function subtrairIntervalo(array $intervalos, int $inicio, int $fim): array
    {
        $resultado = [];

        foreach ($intervalos as [$intervaloInicio, $intervaloFim]) {
            if ($fim <= $intervaloInicio || $inicio >= $intervaloFim) {
                // sem sobreposição, intervalo original permanece intacto
                $resultado[] = [$intervaloInicio, $intervaloFim];

                continue;
            }

            if ($inicio > $intervaloInicio) {
                $resultado[] = [$intervaloInicio, min($inicio, $intervaloFim)];
            }

            if ($fim < $intervaloFim) {
                $resultado[] = [max($fim, $intervaloInicio), $intervaloFim];
            }
        }

        return $resultado;
    }

    /**
     * @param  array<int, array{0: int, 1: int}>  $intervalos
     * @return array<int, array{inicio: string, fim: string}>
     */
    private function fatiarEmSlots(array $intervalos, CarbonImmutable $data, ConfiguracaoGlobal $config): array
    {
        $duracao = $config->duracao_consulta_min;
        $intervaloEntre = $config->intervalo_entre_consultas_min;
        $passo = $duracao + $intervaloEntre;

        $agora = CarbonImmutable::now();
        $minimoMinutos = $agora->addHours($config->antecedencia_minima_horas);
        $maximoData = $agora->addDays($config->antecedencia_maxima_dias);

        $slots = [];

        foreach ($intervalos as [$inicio, $fim]) {
            for ($slotInicio = $inicio; $slotInicio + $duracao <= $fim; $slotInicio += $passo) {
                $slotFim = $slotInicio + $duracao;

                $inicioAbsoluto = $data->addMinutes($slotInicio);

                if ($inicioAbsoluto->lt($minimoMinutos) || $inicioAbsoluto->gt($maximoData)) {
                    continue;
                }

                $slots[] = [
                    'inicio' => $this->paraHoraMinuto($slotInicio),
                    'fim' => $this->paraHoraMinuto($slotFim),
                ];
            }
        }

        return $slots;
    }

    /**
     * Público (não é regra de negócio, só conversão de formato) para que a
     * camada administrativa trabalhe com os mesmos "minutos desde
     * a meia-noite" ao comparar consultas existentes contra
     * intervalosAbertosDoDia(), sem reimplementar o parsing.
     */
    public function paraMinutos(string $horaHms): int
    {
        [$h, $m] = explode(':', $horaHms);

        return ((int) $h * 60) + (int) $m;
    }

    public function paraHoraMinuto(int $minutos): string
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }
}
