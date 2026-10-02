<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\TransicaoDeStatusInvalidaException;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Tratamento;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Agenda administrativa. Nenhuma regra de disponibilidade ou de transição de
 * status vive aqui — este controller só traduz HTTP <-> os serviços de
 * domínio (AvailabilityService/AppointmentService).
 */
class AdminAgendaController extends Controller
{
    public function __construct(
        private readonly AppointmentService $appointments,
        private readonly AvailabilityService $availability,
    ) {}

    public function index(Request $request): View
    {
        $validado = $request->validate([
            'visao' => ['nullable', Rule::in(['dia', 'semana'])],
            'data' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::in(array_keys(Appointment::STATUS_LABELS))],
            'tratamento_id' => ['nullable', 'integer', 'exists:tratamentos,id'],
            'busca' => ['nullable', 'string', 'max:190'],
        ]);

        $visao = $validado['visao'] ?? 'dia';
        $dataSelecionada = isset($validado['data'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $validado['data'])->startOfDay()
            : CarbonImmutable::now()->startOfDay();

        $inicioIntervalo = $visao === 'semana'
            ? $dataSelecionada->startOfWeek(CarbonImmutable::MONDAY)
            : $dataSelecionada;
        $fimIntervalo = $visao === 'semana'
            ? $inicioIntervalo->addDays(6)
            : $dataSelecionada;

        // A listagem nunca carrega e-mail/telefone: esses campos só saem do
        // banco quando o admin abre o detalhe de UM agendamento (ver show()).
        $consultas = Appointment::query()
            ->select(['id', 'tratamento_id', 'nome_cliente', 'data', 'hora_inicio', 'hora_fim', 'status'])
            ->with('tratamento:id,nome')
            ->whereBetween('data', [$inicioIntervalo->toDateString(), $fimIntervalo->toDateString()])
            ->when($validado['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validado['tratamento_id'] ?? null, fn ($query, $id) => $query->where('tratamento_id', $id))
            ->when($validado['busca'] ?? null, fn ($query, $busca) => $this->aplicarBusca($query, $busca))
            ->orderBy('data')
            ->orderBy('hora_inicio')
            ->get();

        $consultasPorDia = $consultas->groupBy(fn (Appointment $appointment) => $appointment->data->toDateString());

        // Agenda de recurso único: o tratamento não influencia os horários
        // livres, só é exigido pela assinatura do AvailabilityService.
        $horariosLivres = [];
        if ($visao === 'dia') {
            $referencia = Tratamento::query()->orderBy('id')->first();

            if ($referencia) {
                $horariosLivres = $this->availability->horariosDisponiveis($referencia->id, $dataSelecionada);
            }
        }

        return view('admin.agenda.index', [
            'visao' => $visao,
            'dataSelecionada' => $dataSelecionada,
            'inicioIntervalo' => $inicioIntervalo,
            'fimIntervalo' => $fimIntervalo,
            'consultasPorDia' => $consultasPorDia,
            'horariosLivres' => $horariosLivres,
            'tratamentos' => Tratamento::query()->ordenado()->get(['id', 'nome']),
            'statusFiltro' => $validado['status'] ?? null,
            'tratamentoFiltro' => $validado['tratamento_id'] ?? null,
            'buscaFiltro' => $validado['busca'] ?? null,
        ]);
    }

    /**
     * Busca textual por nome, e-mail ou telefone — condição adicional aos
     * filtros de data/status/tratamento. Telefone comparado só por dígitos,
     * independente da máscara digitada.
     */
    private function aplicarBusca($query, string $busca)
    {
        $soDigitos = preg_replace('/\D+/', '', $busca);

        return $query->where(function ($query) use ($busca, $soDigitos) {
            $query->where('nome_cliente', 'ilike', "%{$busca}%")
                ->orWhere('email_cliente', 'ilike', "%{$busca}%");

            if ($soDigitos !== '') {
                $query->orWhereRaw(
                    "REPLACE(REPLACE(REPLACE(REPLACE(telefone_cliente, ' ', ''), '(', ''), ')', ''), '-', '') LIKE ?",
                    ["%{$soDigitos}%"]
                );
            }
        });
    }

    /**
     * Detalhe completo de UM agendamento — buscado sob demanda (fetch) só
     * quando o admin abre o card.
     */
    public function show(Appointment $appointment): JsonResponse
    {
        $appointment->load('tratamento:id,nome');

        return response()->json([
            'id' => $appointment->id,
            'nome_cliente' => $appointment->nome_cliente,
            'email_cliente' => $appointment->email_cliente,
            'telefone_cliente' => $appointment->telefone_cliente,
            'whatsapp_url' => $this->linkWhatsApp($appointment->telefone_cliente),
            'tratamento' => $appointment->tratamento->nome,
            'data' => $appointment->data->format('d/m/Y'),
            'hora_inicio' => substr($appointment->hora_inicio, 0, 5),
            'hora_fim' => substr($appointment->hora_fim, 0, 5),
            'status' => $appointment->status,
            'status_label' => Appointment::STATUS_LABELS[$appointment->status] ?? $appointment->status,
            'cancelled_reason' => $appointment->cancelled_reason,
            'created_at' => $appointment->created_at?->format('d/m/Y H:i'),
        ]);
    }

    public function confirmar(Appointment $appointment): RedirectResponse
    {
        try {
            $this->appointments->confirmar($appointment);
        } catch (TransicaoDeStatusInvalidaException $e) {
            return back()->withErrors(['acao' => $e->getMessage()]);
        }

        return back()->with('status', 'Agendamento confirmado.');
    }

    public function cancelar(Request $request, Appointment $appointment): RedirectResponse
    {
        $validado = $request->validate([
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->appointments->cancelar($appointment, $validado['motivo'] ?? null);
        } catch (TransicaoDeStatusInvalidaException $e) {
            return back()->withErrors(['acao' => $e->getMessage()]);
        }

        return back()->with('status', 'Agendamento cancelado.');
    }

    public function concluir(Appointment $appointment): RedirectResponse
    {
        try {
            $this->appointments->marcarComoConcluido($appointment);
        } catch (TransicaoDeStatusInvalidaException $e) {
            return back()->withErrors(['acao' => $e->getMessage()]);
        }

        return back()->with('status', 'Consulta marcada como concluída.');
    }

    /**
     * Link para iniciar conversa no WhatsApp com o número informado pelo
     * paciente (assume Brasil quando o DDI não foi digitado).
     */
    private function linkWhatsApp(string $telefone): ?string
    {
        $digitos = preg_replace('/\D+/', '', $telefone);

        if (strlen($digitos) < 10 || str_starts_with($digitos, '00')) {
            return null;
        }

        if (strlen($digitos) <= 11) {
            $digitos = '55'.$digitos;
        }

        return "https://wa.me/{$digitos}";
    }
}
