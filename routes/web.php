<?php

use App\Http\Controllers\Admin\AdminAgendaController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminAvailabilityController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\AppointmentController;
use App\Models\Tratamento;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.home', [
        'tratamentos' => Tratamento::query()->ativo()->ordenado()->where('slug', '!=', 'avaliacao')->get(),
    ]);
})->name('home');

Route::prefix('agendamento')->name('agendamento.')->group(function () {
    Route::get('/', [AppointmentController::class, 'create'])->name('create');
    Route::get('/disponibilidade', [AppointmentController::class, 'disponibilidadeMensal'])
        ->middleware('throttle:60,1')
        ->name('disponibilidade');
    Route::get('/horarios', [AppointmentController::class, 'horariosDoDia'])
        ->middleware('throttle:60,1')
        ->name('horarios');
    Route::post('/', [AppointmentController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('store');
    Route::get('/{appointment}/confirmado', [AppointmentController::class, 'confirmado'])
        ->middleware('signed')
        ->name('confirmado');
});

/**
 * Painel administrativo simplificado da demo: login, dashboard, agenda e
 * disponibilidade. Sem recuperação de senha (não há provedor de e-mail).
 */
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');

        Route::middleware('can:access-admin-panel')->group(function () {
            Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

            Route::prefix('agenda')->group(function () {
                Route::get('/', [AdminAgendaController::class, 'index'])->name('agenda');
                Route::get('/{appointment}', [AdminAgendaController::class, 'show'])->name('agenda.show');
                Route::post('/{appointment}/confirmar', [AdminAgendaController::class, 'confirmar'])->name('agenda.confirmar');
                Route::post('/{appointment}/cancelar', [AdminAgendaController::class, 'cancelar'])->name('agenda.cancelar');
                Route::post('/{appointment}/concluir', [AdminAgendaController::class, 'concluir'])->name('agenda.concluir');
            });

            Route::prefix('disponibilidade')->name('disponibilidade.')->group(function () {
                Route::get('/', [AdminAvailabilityController::class, 'index'])->name('index');
                Route::post('/horarios/{diaDaSemana}', [AdminAvailabilityController::class, 'atualizarRegra'])
                    ->whereNumber('diaDaSemana')
                    ->name('horarios.atualizar');
                Route::post('/duracao', [AdminAvailabilityController::class, 'atualizarDuracao'])->name('duracao.atualizar');
                Route::post('/bloqueios', [AdminAvailabilityController::class, 'salvarBloqueio'])->name('bloqueios.salvar');
                Route::delete('/bloqueios/{bloqueio}', [AdminAvailabilityController::class, 'removerBloqueio'])->name('bloqueios.remover');
            });
        });
    });
});
