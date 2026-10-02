<?php

namespace Tests\Feature;

use App\Models\Tratamento;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\SeedsAgendaFixtures;
use Tests\TestCase;

/**
 * Cobre o fluxo público de agendamento: página, endpoints de
 * disponibilidade/horários e a submissão real via AppointmentService —
 * sem duplicar nenhuma regra de negócio, só verificando a integração HTTP.
 */
class AgendamentoPublicoTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAgendaFixtures;

    private Tratamento $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->area = $this->criarTratamento();
        $this->criarExpedientePadrao();
        $this->criarConfiguracaoGlobal();
    }

    private function dadosValidos(\Carbon\CarbonImmutable $data, string $horaInicio): array
    {
        return [
            'tratamento_id' => $this->area->id,
            'nome_cliente' => 'Cliente Teste',
            'email_cliente' => 'cliente@example.com',
            'telefone_cliente' => '11999999999',
            'data' => $data->toDateString(),
            'hora_inicio' => $horaInicio,
            'consentimento' => '1',
        ];
    }

    /**
     * Data garantidamente anterior a "agora" (fuso da aplicação), para o
     * teste de rejeição de data passada. Não usa "próxima segunda - 1
     * semana": quando o teste roda numa segunda-feira, isso resultaria na
     * própria data atual, não numa data passada — 30 dias atrás elimina
     * essa coincidência sem depender do dia da semana em que os testes rodam.
     */
    private function dataInequivocamentePassada(): \Carbon\CarbonImmutable
    {
        return \Carbon\CarbonImmutable::now()->subDays(30)->startOfDay();
    }

    public function test_pagina_de_agendamento_carrega_e_lista_areas_ativas(): void
    {
        $resposta = $this->get(route('agendamento.create'));

        $resposta->assertOk();
        $resposta->assertSee('Agende sua');
        $resposta->assertSee($this->area->nome);
    }

    public function test_endpoint_de_horarios_nao_retorna_horario_ja_ocupado(): void
    {
        $segunda = $this->proximaSegundaFeira();

        Appointment::query()->create([
            'tratamento_id' => $this->area->id,
            'nome_cliente' => 'Outro Cliente',
            'email_cliente' => 'outro@example.com',
            'telefone_cliente' => '11888888888',
            'data' => $segunda->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fim' => '11:00:00',
        ]);

        $resposta = $this->getJson(route('agendamento.horarios', [
            'tratamento_id' => $this->area->id,
            'data' => $segunda->toDateString(),
        ]));

        $resposta->assertOk();
        $horarios = collect($resposta->json('horarios'))->pluck('inicio');

        $this->assertNotContains('10:00', $horarios);
        $this->assertContains('09:00', $horarios);
        $this->assertContains('11:00', $horarios);
    }

    public function test_formulario_invalido_retorna_erros_de_validacao_e_nao_cria_agendamento(): void
    {
        $resposta = $this->post(route('agendamento.store'), [
            'tratamento_id' => $this->area->id,
            // nome, e-mail, telefone e consentimento propositalmente ausentes
            'data' => $this->proximaSegundaFeira()->toDateString(),
            'hora_inicio' => '09:00',
        ]);

        $resposta->assertSessionHasErrors(['nome_cliente', 'email_cliente', 'telefone_cliente', 'consentimento']);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_consentimento_nao_marcado_e_rejeitado(): void
    {
        $dados = $this->dadosValidos($this->proximaSegundaFeira(), '09:00');
        unset($dados['consentimento']);

        $resposta = $this->post(route('agendamento.store'), $dados);

        $resposta->assertSessionHasErrors('consentimento');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_agendamento_valido_cria_registro_confirmado_e_redireciona_para_confirmacao(): void
    {
        $segunda = $this->proximaSegundaFeira();

        $resposta = $this->post(route('agendamento.store'), $this->dadosValidos($segunda, '09:00'));

        $appointment = Appointment::query()->firstOrFail();

        $resposta->assertRedirect();
        $this->assertStringContainsString(
            "/agendamento/{$appointment->id}/confirmado",
            $resposta->headers->get('Location')
        );
        $this->assertStringContainsString('signature=', $resposta->headers->get('Location'));

        $this->assertSame(Appointment::STATUS_CONFIRMED, $appointment->status);
        $this->assertSame('Cliente Teste', $appointment->nome_cliente);
        $this->assertSame($segunda->toDateString(), $appointment->data->toDateString());
    }

    // ==========================================================
    // ==========================================================

    public function test_agendamento_duplicado_no_mesmo_horario_e_rejeitado_com_mensagem_amigavel(): void
    {
        $segunda = $this->proximaSegundaFeira();

        $this->post(route('agendamento.store'), $this->dadosValidos($segunda, '09:00'))->assertRedirect();
        $this->assertDatabaseCount('appointments', 1);

        // Segunda pessoa tentando o mesmo horário (ex.: ficou ocupado durante o preenchimento).
        $resposta = $this->post(route('agendamento.store'), array_merge(
            $this->dadosValidos($segunda, '09:00'),
            ['nome_cliente' => 'Segundo Cliente', 'email_cliente' => 'segundo@example.com']
        ));

        $resposta->assertSessionHasErrors('hora_inicio');
        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseMissing('appointments', ['nome_cliente' => 'Segundo Cliente']);
    }

    public function test_pagina_de_confirmacao_exige_url_assinada(): void
    {
        $segunda = $this->proximaSegundaFeira();
        $this->post(route('agendamento.store'), $this->dadosValidos($segunda, '09:00'));
        $appointment = Appointment::query()->firstOrFail();

        // Acesso direto sem assinatura válida — não deve expor dados de terceiros.
        $resposta = $this->get("/agendamento/{$appointment->id}/confirmado");

        $resposta->assertForbidden();
    }

    public function test_pagina_de_confirmacao_exibe_area_data_e_horario_corretos(): void
    {
        $segunda = $this->proximaSegundaFeira();
        $redirect = $this->post(route('agendamento.store'), $this->dadosValidos($segunda, '09:00'));
        $appointment = Appointment::query()->firstOrFail();

        $resposta = $this->get($redirect->headers->get('Location'));

        $resposta->assertOk();
        $resposta->assertSee('Solicitação enviada!');
        $resposta->assertSee($appointment->tratamento->nome);
        $resposta->assertSee('09:00');
    }

    /**
     * Regra: uma área de atuação com ativo=false não pode
     * receber novos agendamentos — nem confiando só no formulário/frontend.
     */
    public function test_post_com_area_de_atuacao_inativa_e_rejeitado(): void
    {
        $areaInativa = Tratamento::query()->create([
            'slug' => 'direito-inativo',
            'nome' => 'Área Inativa',
            'ativo' => false,
        ]);

        $dados = $this->dadosValidos($this->proximaSegundaFeira(), '09:00');
        $dados['tratamento_id'] = $areaInativa->id;

        $resposta = $this->post(route('agendamento.store'), $dados);

        $resposta->assertSessionHasErrors('tratamento_id');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_post_com_data_passada_e_rejeitado(): void
    {
        $resposta = $this->post(route('agendamento.store'), $this->dadosValidos($this->dataInequivocamentePassada(), '09:00'));

        $resposta->assertSessionHasErrors('hora_inicio');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_post_com_horario_fora_da_grade_e_rejeitado(): void
    {
        // 08:00 é anterior à abertura do expediente padrão (09:00–18:00).
        $resposta = $this->post(route('agendamento.store'), $this->dadosValidos($this->proximaSegundaFeira(), '08:00'));

        $resposta->assertSessionHasErrors('hora_inicio');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_endpoint_de_disponibilidade_com_area_inexistente_retorna_422(): void
    {
        $resposta = $this->getJson(route('agendamento.disponibilidade', [
            'tratamento_id' => 99999,
            'mes' => $this->proximaSegundaFeira()->format('Y-m'),
        ]));

        $resposta->assertStatus(422);
    }

    public function test_endpoint_de_horarios_com_area_inexistente_retorna_422(): void
    {
        $resposta = $this->getJson(route('agendamento.horarios', [
            'tratamento_id' => 99999,
            'data' => $this->proximaSegundaFeira()->toDateString(),
        ]));

        $resposta->assertStatus(422);
    }

    public function test_rate_limit_do_post_retorna_429_apos_exceder_o_limite(): void
    {
        $segunda = $this->proximaSegundaFeira();

        // A rota de store está limitada a 10 requisições/minuto (routes/web.php).
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('agendamento.store'), $this->dadosValidos($segunda, '09:00'));
        }

        $resposta = $this->post(route('agendamento.store'), $this->dadosValidos($segunda, '09:00'));

        $resposta->assertStatus(429);
    }

    /**
     * Regra: a URL de confirmação usa
     * URL::temporarySignedRoute() com validade de 2 dias.
     */
    public function test_url_assinada_valida_permite_acesso_a_confirmacao(): void
    {
        $segunda = $this->proximaSegundaFeira();
        $redirect = $this->post(route('agendamento.store'), $this->dadosValidos($segunda, '09:00'));

        $resposta = $this->get($redirect->headers->get('Location'));

        $resposta->assertOk();
    }

    public function test_assinatura_invalida_e_rejeitada(): void
    {
        $segunda = $this->proximaSegundaFeira();
        $redirect = $this->post(route('agendamento.store'), $this->dadosValidos($segunda, '09:00'));
        $appointment = Appointment::query()->firstOrFail();

        $urlComAssinaturaInvalida = preg_replace(
            '/signature=[^&]+/',
            'signature=assinatura-forjada',
            $redirect->headers->get('Location')
        );

        $resposta = $this->get($urlComAssinaturaInvalida);

        $resposta->assertForbidden();
        $this->assertNotNull($appointment);
    }

    public function test_url_de_confirmacao_expirada_e_rejeitada(): void
    {
        $segunda = $this->proximaSegundaFeira();
        $redirect = $this->post(route('agendamento.store'), $this->dadosValidos($segunda, '09:00'));
        $url = $redirect->headers->get('Location');

        // A assinatura é válida por 2 dias; avançamos
        // 3 dias no relógio para simular o link já expirado.
        Carbon::setTestNow(Carbon::now()->addDays(3));

        try {
            $resposta = $this->get($url);
            $resposta->assertForbidden();
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_assinatura_gerada_para_outro_agendamento_nao_da_acesso(): void
    {
        $segunda = $this->proximaSegundaFeira();

        $redirectA = $this->post(route('agendamento.store'), $this->dadosValidos($segunda, '09:00'));
        $redirectB = $this->post(route('agendamento.store'), array_merge(
            $this->dadosValidos($segunda, '10:00'),
            ['nome_cliente' => 'Outro Cliente', 'email_cliente' => 'outro@example.com']
        ));

        $appointmentA = Appointment::query()->where('hora_inicio', '09:00:00')->firstOrFail();
        $appointmentB = Appointment::query()->where('hora_inicio', '10:00:00')->firstOrFail();

        // Pega a URL assinada de A e troca só o ID do agendamento na rota,
        // mantendo a assinatura original (gerada para A) — não deve dar
        // acesso aos dados de B.
        $urlDeA = $redirectA->headers->get('Location');
        $urlForjadaParaB = str_replace(
            "/agendamento/{$appointmentA->id}/confirmado",
            "/agendamento/{$appointmentB->id}/confirmado",
            $urlDeA
        );

        $resposta = $this->get($urlForjadaParaB);

        $resposta->assertForbidden();
    }
}
