<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\AlteracaoDeAgendaAfetaConsultasException;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use App\Models\ConfiguracaoGlobal;
use App\Services\AdminAvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Disponibilidade (versão simplificada da demo): horário de cada dia da
 * semana, duração da consulta e bloqueios (dia inteiro ou faixa de horário).
 * Nenhuma regra de conflito/cancelamento vive aqui — só HTTP <->
 * AdminAvailabilityService.
 */
class AdminAvailabilityController extends Controller
{
    public function __construct(private readonly AdminAvailabilityService $disponibilidade) {}

    public function index(): View
    {
        $regrasPorDia = AvailabilityRule::query()->get()->keyBy('day_of_week');

        // Segunda a domingo (1..6, depois 0) — ordem de leitura natural.
        $ordemExibicao = [1, 2, 3, 4, 5, 6, 0];
        $nomesDias = [0 => 'Domingo', 1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta', 6 => 'Sábado'];

        $dias = [];
        foreach ($ordemExibicao as $diaDaSemana) {
            $regra = $regrasPorDia->get($diaDaSemana);
            $dias[] = [
                'dia' => $diaDaSemana,
                'nome' => $nomesDias[$diaDaSemana],
                'hora_inicio' => $regra ? substr($regra->hora_inicio, 0, 5) : '09:00',
                'hora_fim' => $regra ? substr($regra->hora_fim, 0, 5) : '18:00',
                'ativo' => $regra?->ativo ?? false,
            ];
        }

        return view('admin.disponibilidade.index', [
            'dias' => $dias,
            'duracaoConsulta' => ConfiguracaoGlobal::atual()->duracao_consulta_min,
            'bloqueios' => AvailabilityException::query()
                ->whereIn('tipo', [AvailabilityException::TIPO_FECHADO_TOTAL, AvailabilityException::TIPO_FECHADO_PARCIAL])
                ->whereDate('data', '>=', now()->toDateString())
                ->orderBy('data')
                ->orderBy('hora_inicio')
                ->get(),
        ]);
    }

    public function atualizarRegra(Request $request, int $diaDaSemana): RedirectResponse
    {
        abort_unless($diaDaSemana >= 0 && $diaDaSemana <= 6, 404);

        $dados = $request->validate([
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fim' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'ativo' => ['required', 'boolean'],
        ]);

        try {
            $canceladas = $this->disponibilidade->atualizarRegraSemanal($diaDaSemana, [
                'hora_inicio' => "{$dados['hora_inicio']}:00",
                'hora_fim' => "{$dados['hora_fim']}:00",
                'ativo' => (bool) $dados['ativo'],
            ], $request->user(), $request->boolean('confirmar_cancelamento'));
        } catch (AlteracaoDeAgendaAfetaConsultasException $e) {
            return back()->withInput()->with('conflito_pendente', [
                'tipo' => 'regra',
                'dia_da_semana' => $diaDaSemana,
                'afetadas' => $this->resumoAfetadas($e->consultasAfetadas),
            ]);
        }

        return back()->with('status', $this->mensagemSucesso('Horário salvo', $canceladas));
    }

    public function atualizarDuracao(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'duracao_consulta_min' => ['required', 'integer', Rule::in([30, 45, 60, 90, 120])],
        ]);

        $this->disponibilidade->atualizarDuracaoConsulta((int) $dados['duracao_consulta_min'], $request->user());

        return back()->with('status', 'Duração da consulta atualizada.');
    }

    public function salvarBloqueio(Request $request): RedirectResponse
    {
        $ehFaixaDeHorario = $request->input('tipo') === AvailabilityException::TIPO_FECHADO_PARCIAL;

        $dados = $request->validate([
            'tipo' => ['required', Rule::in([AvailabilityException::TIPO_FECHADO_PARCIAL, AvailabilityException::TIPO_FECHADO_TOTAL])],
            'data' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora_inicio' => [$ehFaixaDeHorario ? 'required' : 'nullable', 'date_format:H:i'],
            'hora_fim' => [$ehFaixaDeHorario ? 'required' : 'nullable', 'date_format:H:i', 'after:hora_inicio'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $canceladas = $this->disponibilidade->salvarExcecao(null, [
                'data' => $dados['data'],
                'tipo' => $dados['tipo'],
                'hora_inicio' => $ehFaixaDeHorario ? "{$dados['hora_inicio']}:00" : null,
                'hora_fim' => $ehFaixaDeHorario ? "{$dados['hora_fim']}:00" : null,
                'motivo' => $dados['motivo'] ?? null,
            ], $request->user(), $request->boolean('confirmar_cancelamento'));
        } catch (AlteracaoDeAgendaAfetaConsultasException $e) {
            return back()->withInput()->with('conflito_pendente', [
                'tipo' => 'bloqueio',
                'afetadas' => $this->resumoAfetadas($e->consultasAfetadas),
            ]);
        }

        return back()->with('status', $this->mensagemSucesso('Bloqueio salvo', $canceladas));
    }

    public function removerBloqueio(Request $request, AvailabilityException $bloqueio): RedirectResponse
    {
        $this->disponibilidade->removerExcecao($bloqueio, $request->user());

        return back()->with('status', 'Bloqueio removido.');
    }

    private function mensagemSucesso(string $rotulo, Collection $canceladas): string
    {
        if ($canceladas->isEmpty()) {
            return "{$rotulo} com sucesso.";
        }

        return "{$rotulo} com sucesso. {$canceladas->count()} agendamento(s) cancelado(s).";
    }

    /**
     * @return array<int, array{id:int,data:string,hora_inicio:string,hora_fim:string,nome_cliente:string}>
     */
    private function resumoAfetadas(Collection $afetadas): array
    {
        return $afetadas->map(fn (Appointment $a) => [
            'id' => $a->id,
            'data' => $a->data->toDateString(),
            'hora_inicio' => substr($a->hora_inicio, 0, 5),
            'hora_fim' => substr($a->hora_fim, 0, 5),
            'nome_cliente' => $a->nome_cliente,
        ])->values()->all();
    }
}
