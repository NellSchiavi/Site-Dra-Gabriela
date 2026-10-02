<?php

namespace Tests\Feature;

use App\Exceptions\AlteracaoDeAgendaAfetaConsultasException;
use App\Models\Tratamento;
use App\Models\Appointment;
use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use App\Models\ConfiguracaoGlobal;
use App\Models\User;
use App\Services\AdminAvailabilityService;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Database\Seeders\AvailabilityRuleSeeder;
use Database\Seeders\ConfiguracaoGlobalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\SeedsAgendaFixtures;
use Tests\TestCase;

/**
 * Configuração administrativa da agenda (horários semanais e
 * bloqueios) — sempre convergindo para o mesmo
 * AvailabilityService, nunca uma segunda lógica de disponibilidade.
 */
class AdminAvailabilityTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAgendaFixtures;

    private User $admin;

    private Tratamento $area;

    private AdminAvailabilityService $disponibilidade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create()->fresh();
        $this->area = $this->criarTratamento();
        $this->criarExpedientePadrao();
        $this->criarConfiguracaoGlobal();
        $this->disponibilidade = app(AdminAvailabilityService::class);
    }

    private function criarConsulta(\Carbon\CarbonImmutable $data, string $horaInicio, string $horaFim): Appointment
    {
        $consulta = Appointment::query()->create([
            'tratamento_id' => $this->area->id,
            'nome_cliente' => 'Cliente Teste',
            'email_cliente' => 'cliente@example.com',
            'telefone_cliente' => '11999999999',
            'data' => $data->toDateString(),
            'hora_inicio' => $horaInicio,
            'hora_fim' => $horaFim,
        ]);
        $consulta->status = Appointment::STATUS_CONFIRMED;
        $consulta->save();

        return $consulta->fresh();
    }

    // =====================================================================
    // Seeders: horários provisórios da demo
    // =====================================================================

    public function test_seeder_configura_segunda_a_sexta_das_9h_as_18h(): void
    {
        $this->seed(AvailabilityRuleSeeder::class);

        $regras = AvailabilityRule::query()->get()->keyBy('day_of_week');

        foreach ([1, 2, 3, 4, 5] as $dia) {
            $this->assertSame('09:00:00', $regras[$dia]->hora_inicio);
            $this->assertSame('18:00:00', $regras[$dia]->hora_fim);
            $this->assertTrue((bool) $regras[$dia]->ativo);
        }
    }

    public function test_seeder_configura_sabado_das_9h_as_13h_e_domingo_fechado(): void
    {
        $this->seed(AvailabilityRuleSeeder::class);

        $sabado = AvailabilityRule::query()->where('day_of_week', 6)->firstOrFail();

        $this->assertSame('09:00:00', $sabado->hora_inicio);
        $this->assertSame('13:00:00', $sabado->hora_fim);
        $this->assertTrue((bool) $sabado->ativo);
        $this->assertDatabaseMissing('availability_rules', ['day_of_week' => 0]);
    }

    public function test_seeder_configura_duracao_de_60_minutos_e_confirmacao_manual(): void
    {
        // O seeder só preenche a configuração na primeira execução (não
        // sobrescreve ajustes feitos pelo painel a cada deploy).
        ConfiguracaoGlobal::query()->delete();

        $this->seed(ConfiguracaoGlobalSeeder::class);

        $config = ConfiguracaoGlobal::atual();

        $this->assertSame(60, $config->duracao_consulta_min);
        $this->assertSame(0, $config->intervalo_entre_consultas_min);
        $this->assertSame(ConfiguracaoGlobal::MODO_MANUAL, $config->modo_confirmacao);
    }


    public function test_slots_gerados_usam_55_minutos_mais_5_de_intervalo(): void
    {
        $this->criarConfiguracaoGlobal(['duracao_consulta_min' => 55, 'intervalo_entre_consultas_min' => 5]);
        $segunda = $this->proximaSegundaFeira();

        $slots = app(AvailabilityService::class)->horariosDisponiveis($this->area->id, $segunda);

        $this->assertSame(['inicio' => '09:00', 'fim' => '09:55'], $slots[0]);
        $this->assertSame(['inicio' => '10:00', 'fim' => '10:55'], $slots[1]);
        $this->assertSame(['inicio' => '11:00', 'fim' => '11:55'], $slots[2]);
    }

    // =====================================================================
    // Segurança / autorização
    // =====================================================================

    public function test_visitante_nao_autenticado_nao_acessa_disponibilidade(): void
    {
        $resposta = $this->get(route('admin.disponibilidade.index'));

        $resposta->assertRedirect(route('admin.login'));
    }

    public function test_admin_acessa_a_pagina_de_disponibilidade(): void
    {
        $resposta = $this->actingAs($this->admin)->get(route('admin.disponibilidade.index'));

        $resposta->assertOk();
        $resposta->assertSee('Disponibilidade');
        $resposta->assertSee('Horários da semana');
    }

    public function test_rotas_de_disponibilidade_exigem_autenticacao_e_gate_de_admin(): void
    {
        foreach ([
            'admin.disponibilidade.index',
            'admin.disponibilidade.horarios.atualizar',
            'admin.disponibilidade.duracao.atualizar',
            'admin.disponibilidade.bloqueios.salvar',
            'admin.disponibilidade.bloqueios.remover',
        ] as $nome) {
            $middleware = Route::getRoutes()->getByName($nome)->gatherMiddleware();

            $this->assertContains('auth', $middleware, "Rota {$nome} deveria exigir autenticação.");
            $this->assertContains('can:access-admin-panel', $middleware, "Rota {$nome} deveria exigir o Gate de admin.");
            $this->assertContains('web', $middleware, "Rota {$nome} deveria estar no grupo web (CSRF).");
        }
    }

    // =====================================================================
    // Horário padrão semanal
    // =====================================================================

    public function test_admin_atualiza_horario_padrao_sem_consultas_afetadas(): void
    {
        $resposta = $this->actingAs($this->admin)->post(route('admin.disponibilidade.horarios.atualizar', 1), [
            'hora_inicio' => '08:00',
            'hora_fim' => '19:00',
            'ativo' => '1',
        ]);

        $resposta->assertRedirect();
        $resposta->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('availability_rules', [
            'day_of_week' => 1,
            'hora_inicio' => '08:00:00',
            'hora_fim' => '19:00:00',
        ]);
    }

    public function test_atualizacao_de_horario_com_consulta_afetada_exige_confirmacao_e_nao_aplica(): void
    {
        $segunda = $this->proximaSegundaFeira();
        $consulta = $this->criarConsulta($segunda, '16:00:00', '17:00:00');

        try {
            $this->disponibilidade->atualizarRegraSemanal(1, [
                'hora_inicio' => '09:00:00',
                'hora_fim' => '15:00:00',
                'ativo' => true,
            ], $this->admin, false);
            $this->fail('Deveria ter lançado AlteracaoDeAgendaAfetaConsultasException.');
        } catch (AlteracaoDeAgendaAfetaConsultasException $e) {
            $this->assertCount(1, $e->consultasAfetadas);
            $this->assertSame($consulta->id, $e->consultasAfetadas->first()->id);
        }

        // Nada foi persistido — rollback completo (item 7).
        $this->assertDatabaseHas('availability_rules', ['day_of_week' => 1, 'hora_fim' => '18:00:00']);
        $this->assertSame(Appointment::STATUS_CONFIRMED, $consulta->fresh()->status);
        // alteração não confirmada não cancela e, portanto,
        // não deve disparar nenhum e-mail de cancelamento.
    }

    public function test_consulta_nao_afetada_permanece_confirmada_apos_alteracao_de_outro_dia(): void
    {
        $segunda = $this->proximaSegundaFeira();
        $terca = $segunda->addDay();
        $consultaTerca = $this->criarConsulta($terca, '16:00:00', '17:00:00');

        // Estreita SEGUNDA (não afeta a consulta de terça).
        $this->disponibilidade->atualizarRegraSemanal(1, [
            'hora_inicio' => '09:00:00',
            'hora_fim' => '15:00:00',
            'ativo' => true,
        ], $this->admin, false);

        $this->assertSame(Appointment::STATUS_CONFIRMED, $consultaTerca->fresh()->status);
    }

    // =====================================================================
    // Exceções de horário
    // =====================================================================

    public function test_admin_bloqueia_faixa_de_horario(): void
    {
        $data = $this->proximaSegundaFeira()->addWeek();

        $resposta = $this->actingAs($this->admin)->post(route('admin.disponibilidade.bloqueios.salvar'), [
            'tipo' => AvailabilityException::TIPO_FECHADO_PARCIAL,
            'data' => $data->toDateString(),
            'hora_inicio' => '14:00',
            'hora_fim' => '16:00',
        ]);

        $resposta->assertRedirect();
        $this->assertDatabaseHas('availability_exceptions', [
            'data' => $data->toDateString(),
            'tipo' => AvailabilityException::TIPO_FECHADO_PARCIAL,
            'hora_inicio' => '14:00:00',
            'hora_fim' => '16:00:00',
        ]);

        $inicios = array_column(app(AvailabilityService::class)->horariosDisponiveis($this->area->id, $data), 'inicio');
        $this->assertNotContains('14:00', $inicios);
        $this->assertNotContains('15:00', $inicios);
        $this->assertContains('16:00', $inicios);
    }

    public function test_excecao_substitui_a_regra_padrao_e_reduz_o_horario(): void
    {
        $sexta = $this->proximaSegundaFeira()->addDays(4); // sexta da mesma semana
        $this->criarExpedientePadrao(); // garante regra base 09-18 pra sexta também (id 5 já coberto)

        AvailabilityException::query()->create([
            'data' => $sexta->toDateString(),
            'tipo' => AvailabilityException::TIPO_HORARIO_ESPECIAL,
            'hora_inicio' => '09:00:00',
            'hora_fim' => '13:00:00',
        ]);

        $slots = app(AvailabilityService::class)->horariosDisponiveis($this->area->id, $sexta);

        $this->assertNotEmpty($slots);
        foreach ($slots as $slot) {
            $this->assertLessThanOrEqual('13:00', $slot['fim']);
        }
    }

    public function test_excecao_conflitante_com_consulta_existente_exige_confirmacao(): void
    {
        $sexta = $this->proximaSegundaFeira()->addDays(4);
        $consulta = $this->criarConsulta($sexta, '15:00:00', '16:00:00');

        try {
            $this->disponibilidade->salvarExcecao(null, [
                'data' => $sexta->toDateString(),
                'tipo' => AvailabilityException::TIPO_HORARIO_ESPECIAL,
                'hora_inicio' => '09:00:00',
                'hora_fim' => '13:00:00',
                'motivo' => null,
            ], $this->admin, false);
            $this->fail('Deveria ter lançado AlteracaoDeAgendaAfetaConsultasException.');
        } catch (AlteracaoDeAgendaAfetaConsultasException $e) {
            $this->assertCount(1, $e->consultasAfetadas);
        }

        $this->assertDatabaseMissing('availability_exceptions', ['data' => $sexta->toDateString()]);
        $this->assertSame(Appointment::STATUS_CONFIRMED, $consulta->fresh()->status);
    }

    public function test_remocao_de_excecao_restaura_o_calculo_normal(): void
    {
        $segunda = $this->proximaSegundaFeira();
        $excecao = AvailabilityException::query()->create([
            'data' => $segunda->toDateString(),
            'tipo' => AvailabilityException::TIPO_HORARIO_ESPECIAL,
            'hora_inicio' => '09:00:00',
            'hora_fim' => '11:00:00',
        ]);

        $availability = app(AvailabilityService::class);
        $antesDaRemocao = $availability->horariosDisponiveis($this->area->id, $segunda);
        $this->assertLessThanOrEqual('11:00', collect($antesDaRemocao)->last()['fim']);

        $this->disponibilidade->removerExcecao($excecao, $this->admin);

        $depoisDaRemocao = $availability->horariosDisponiveis($this->area->id, $segunda);
        $this->assertGreaterThan(count($antesDaRemocao), count($depoisDaRemocao));
        $this->assertDatabaseHas('admin_logs', ['acao' => 'disponibilidade.bloqueio_horario.removido']);
    }

    // =====================================================================
    // Bloqueios de dia inteiro
    // =====================================================================

    public function test_dia_bloqueado_nao_aparece_como_disponivel(): void
    {
        $segunda = $this->proximaSegundaFeira();

        $this->disponibilidade->salvarExcecao(null, [
            'data' => $segunda->toDateString(),
            'tipo' => AvailabilityException::TIPO_FECHADO_TOTAL,
            'motivo' => 'Compromisso pessoal',
        ], $this->admin, false);

        $slots = app(AvailabilityService::class)->horariosDisponiveis($this->area->id, $segunda);

        $this->assertEmpty($slots);
    }

    public function test_bloqueio_com_consulta_existente_exige_confirmacao(): void
    {
        $segunda = $this->proximaSegundaFeira();
        $consulta = $this->criarConsulta($segunda, '10:00:00', '11:00:00');

        try {
            $this->disponibilidade->salvarExcecao(null, [
                'data' => $segunda->toDateString(),
                'tipo' => AvailabilityException::TIPO_FECHADO_TOTAL,
                'motivo' => null,
            ], $this->admin, false);
            $this->fail('Deveria ter lançado AlteracaoDeAgendaAfetaConsultasException.');
        } catch (AlteracaoDeAgendaAfetaConsultasException $e) {
            $this->assertCount(1, $e->consultasAfetadas);
            $this->assertSame($consulta->id, $e->consultasAfetadas->first()->id);
        }

        $this->assertDatabaseMissing('availability_exceptions', ['data' => $segunda->toDateString()]);
        $this->assertSame(Appointment::STATUS_CONFIRMED, $consulta->fresh()->status);
    }

    public function test_admin_remove_bloqueio_sem_afetar_consultas(): void
    {
        $segunda = $this->proximaSegundaFeira();
        $bloqueio = AvailabilityException::query()->create([
            'data' => $segunda->toDateString(),
            'tipo' => AvailabilityException::TIPO_FECHADO_TOTAL,
        ]);

        $this->disponibilidade->removerExcecao($bloqueio, $this->admin);

        $this->assertDatabaseMissing('availability_exceptions', ['id' => $bloqueio->id]);
        $this->assertDatabaseHas('admin_logs', ['acao' => 'disponibilidade.bloqueio_dia.removido']);
    }



    // =====================================================================
    // Concorrência (garantia prática: bloqueio confirmado impede nova reserva)
    // =====================================================================

    public function test_apos_bloqueio_confirmado_o_agendamento_publico_e_rejeitado(): void
    {
        $segunda = $this->proximaSegundaFeira();

        $this->disponibilidade->salvarExcecao(null, [
            'data' => $segunda->toDateString(),
            'tipo' => AvailabilityException::TIPO_FECHADO_TOTAL,
            'motivo' => null,
        ], $this->admin, false);

        $this->expectException(\App\Exceptions\HorarioIndisponivelException::class);
        app(AppointmentService::class)->agendar([
            'tratamento_id' => $this->area->id,
            'nome_cliente' => 'Cliente Novo',
            'email_cliente' => 'novo@example.com',
            'telefone_cliente' => '11988887777',
            'data' => $segunda->toDateString(),
            'hora_inicio' => '10:00',
        ]);
    }

    // =====================================================================
    // lock adquirido antes de decidir sobre consultas afetadas
    // =====================================================================

    public function test_lock_de_agenda_e_adquirido_para_as_datas_afetadas_pela_regra_semanal(): void
    {
        $segunda = $this->proximaSegundaFeira();

        $this->disponibilidade->atualizarRegraSemanal(1, [
            'hora_inicio' => '09:00:00', 'hora_fim' => '18:00:00', 'ativo' => true,
        ], $this->admin, false);

        $this->assertDatabaseHas('agenda_locks', ['data' => $segunda->toDateString()]);
    }
}
