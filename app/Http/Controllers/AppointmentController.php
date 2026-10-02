<?php

namespace App\Http\Controllers;

use App\Exceptions\HorarioIndisponivelException;
use App\Http\Requests\AgendarConsultaRequest;
use App\Models\Appointment;
use App\Models\Tratamento;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Fluxo público de agendamento. Nenhuma regra de disponibilidade ou de
 * concorrência vive aqui — este controller só traduz HTTP <-> os serviços
 * de domínio (AvailabilityService/AppointmentService).
 */
class AppointmentController extends Controller
{
    public function create(Request $request): View
    {
        $tratamentos = Tratamento::query()->ativo()->ordenado()->get(['id', 'slug', 'nome', 'icone', 'descricao']);

        // Atalho vindo dos cards de tratamento da home (?tratamento=slug):
        // o assistente já abre com o tratamento escolhido.
        $preSelecionado = $tratamentos->firstWhere('slug', (string) $request->query('tratamento'));

        return view('pages.agendamento', [
            'tratamentos' => $tratamentos,
            'tratamentoPreSelecionado' => $preSelecionado?->id,
        ]);
    }

    /**
     * Disponibilidade resumida do mês (para desenhar o calendário): para cada
     * dia, apenas se existe ou não pelo menos 1 horário livre.
     */
    public function disponibilidadeMensal(Request $request, AvailabilityService $availability): JsonResponse
    {
        $validated = $request->validate([
            'tratamento_id' => ['required', 'integer', 'exists:tratamentos,id'],
            'mes' => ['required', 'date_format:Y-m'],
        ]);

        $inicioMes = CarbonImmutable::createFromFormat('Y-m-d', $validated['mes'].'-01')->startOfMonth();
        $fimMes = $inicioMes->endOfMonth();
        $hoje = CarbonImmutable::now()->startOfDay();

        $dias = [];
        for ($data = $inicioMes; $data->lte($fimMes); $data = $data->addDay()) {
            // Datas passadas nunca têm horário — evita consultas desnecessárias.
            $dias[$data->toDateString()] = $data->lt($hoje)
                ? false
                : count($availability->horariosDisponiveis((int) $validated['tratamento_id'], $data)) > 0;
        }

        return response()->json(['dias' => $dias]);
    }

    /**
     * Horários realmente disponíveis para uma data específica.
     */
    public function horariosDoDia(Request $request, AvailabilityService $availability): JsonResponse
    {
        $validated = $request->validate([
            'tratamento_id' => ['required', 'integer', 'exists:tratamentos,id'],
            'data' => ['required', 'date_format:Y-m-d'],
        ]);

        $slots = $availability->horariosDisponiveis(
            (int) $validated['tratamento_id'],
            CarbonImmutable::createFromFormat('Y-m-d', $validated['data'])
        );

        return response()->json(['horarios' => $slots]);
    }

    public function store(AgendarConsultaRequest $request, AppointmentService $appointments): RedirectResponse
    {
        try {
            $appointment = $appointments->agendar($request->validated());
        } catch (HorarioIndisponivelException) {
            return back()->withInput()->withErrors([
                'hora_inicio' => 'Este horário acabou de ser reservado. Por favor, escolha outro horário.',
            ]);
        }

        // URL assinada e temporária (2 dias): evita que alguém descubra a
        // solicitação de outra pessoa incrementando o ID na URL.
        return redirect()->to(
            URL::temporarySignedRoute(
                'agendamento.confirmado',
                now()->addDays(2),
                ['appointment' => $appointment->id]
            )
        );
    }

    public function confirmado(Appointment $appointment): View
    {
        $appointment->load('tratamento');

        return view('pages.agendamento-confirmado', ['appointment' => $appointment]);
    }
}
