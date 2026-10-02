<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

/**
 * Dashboard simples: só números e listas que já existem no banco.
 */
class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $hoje = CarbonImmutable::now()->startOfDay();
        $daquiSeteDias = $hoje->addDays(6);

        $colunas = ['id', 'tratamento_id', 'nome_cliente', 'data', 'hora_inicio', 'hora_fim', 'status'];

        return view('admin.dashboard', [
            'consultasHoje' => Appointment::query()->naData($hoje)->ativos()->count(),
            'pendentesTotal' => Appointment::query()
                ->where('status', Appointment::STATUS_PENDING)
                ->whereDate('data', '>=', $hoje)
                ->count(),
            'proximos7Dias' => Appointment::query()
                ->ativos()
                ->whereBetween('data', [$hoje->toDateString(), $daquiSeteDias->toDateString()])
                ->count(),

            'pendentes' => Appointment::query()
                ->select($colunas)
                ->with('tratamento:id,nome')
                ->where('status', Appointment::STATUS_PENDING)
                ->whereDate('data', '>=', $hoje)
                ->orderBy('data')
                ->orderBy('hora_inicio')
                ->limit(10)
                ->get(),

            'proximos' => Appointment::query()
                ->select($colunas)
                ->with('tratamento:id,nome')
                ->where('status', Appointment::STATUS_CONFIRMED)
                ->whereDate('data', '>=', $hoje)
                ->orderBy('data')
                ->orderBy('hora_inicio')
                ->limit(10)
                ->get(),
        ]);
    }
}
