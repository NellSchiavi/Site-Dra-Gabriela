<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ConfiguracaoGlobal;
use App\Models\Tratamento;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DemoAgendamentosSeeder;
use Database\Seeders\TratamentoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\SeedsAgendaFixtures;
use Tests\TestCase;

/**
 * Regras específicas da demo da Dra. Gabriela: confirmação manual das
 * solicitações, ações do painel, proteção das rotas, credenciais só via
 * ambiente, dados de demonstração e conteúdo público.
 */
class DemoGabrielaTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAgendaFixtures;

    private Tratamento $tratamento;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tratamento = $this->criarTratamento();
        $this->criarExpedientePadrao();
        $this->criarConfiguracaoGlobal(['modo_confirmacao' => ConfiguracaoGlobal::MODO_MANUAL]);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    private function consulta(string $status, ?CarbonImmutable $data = null): Appointment
    {
        $consulta = Appointment::query()->create([
            'tratamento_id' => $this->tratamento->id,
            'nome_cliente' => 'Paciente Teste',
            'email_cliente' => 'paciente@example.com',
            'telefone_cliente' => '(11) 98888-7777',
            'data' => ($data ?? $this->proximaSegundaFeira())->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fim' => '11:00:00',
        ]);
        $consulta->status = $status;
        $consulta->save();

        return $consulta->fresh();
    }

    // ---------------------------------------------------------------
    // Fluxo público
    // ---------------------------------------------------------------

    public function test_solicitacao_publica_entra_como_pendente(): void
    {
        $segunda = $this->proximaSegundaFeira();

        $resposta = $this->post(route('agendamento.store'), [
            'tratamento_id' => $this->tratamento->id,
            'nome_cliente' => 'Maria Teste',
            'telefone_cliente' => '(11) 98888-7777',
            'email_cliente' => 'maria@example.com',
            'data' => $segunda->toDateString(),
            'hora_inicio' => '09:00',
            'consentimento' => '1',
        ]);

        $resposta->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'nome_cliente' => 'Maria Teste',
            'status' => Appointment::STATUS_PENDING,
        ]);
    }

    public function test_whatsapp_invalido_e_rejeitado(): void
    {
        $resposta = $this->post(route('agendamento.store'), [
            'tratamento_id' => $this->tratamento->id,
            'nome_cliente' => 'Maria Teste',
            'telefone_cliente' => 'abc',
            'email_cliente' => 'maria@example.com',
            'data' => $this->proximaSegundaFeira()->toDateString(),
            'hora_inicio' => '09:00',
            'consentimento' => '1',
        ]);

        $resposta->assertSessionHasErrors('telefone_cliente');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_tratamento_inativo_e_rejeitado(): void
    {
        $this->tratamento->update(['ativo' => false]);

        $resposta = $this->post(route('agendamento.store'), [
            'tratamento_id' => $this->tratamento->id,
            'nome_cliente' => 'Maria Teste',
            'telefone_cliente' => '(11) 98888-7777',
            'email_cliente' => 'maria@example.com',
            'data' => $this->proximaSegundaFeira()->toDateString(),
            'hora_inicio' => '09:00',
            'consentimento' => '1',
        ]);

        $resposta->assertSessionHasErrors('tratamento_id');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_solicitacao_pendente_ocupa_o_horario(): void
    {
        $consulta = $this->consulta(Appointment::STATUS_PENDING);

        $resposta = $this->getJson(route('agendamento.horarios', [
            'tratamento_id' => $this->tratamento->id,
            'data' => $consulta->data->toDateString(),
        ]));

        $this->assertNotContains('10:00', array_column($resposta->json('horarios'), 'inicio'));
    }

    public function test_link_de_tratamento_da_home_pre_seleciona_o_assistente(): void
    {
        $resposta = $this->get(route('agendamento.create', ['tratamento' => $this->tratamento->slug]));

        $resposta->assertOk();
        $resposta->assertSee('preSelecionado: '.$this->tratamento->id, false);
    }

    public function test_home_exibe_dados_confirmados_e_nao_exibe_horario_provisorio(): void
    {
        $resposta = $this->get(route('home'));

        $resposta->assertOk();
        $resposta->assertSee('Dra. Gabriela Ferri Paes');
        $resposta->assertSee('CRO-SP 113798');
        $resposta->assertSee('R. Biotônico, 1185');
        $resposta->assertSee('Segunda a sábado, somente com hora marcada.');
        $resposta->assertSee('Ambiente de demonstração');
        $resposta->assertDontSee('09:00');
        $resposta->assertDontSee('18:00');
        $resposta->assertDontSee('Depoimentos');
    }

    public function test_respostas_nao_sao_indexaveis_e_sitemap_nao_existe(): void
    {
        $this->get(route('home'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/sitemap.xml')->assertNotFound();
        $this->get('/politica-de-privacidade')->assertNotFound();
        $this->get('/admin/forgot-password')->assertNotFound();
    }

    // ---------------------------------------------------------------
    // Painel
    // ---------------------------------------------------------------

    public function test_rotas_do_painel_exigem_autenticacao_e_gate_de_admin(): void
    {
        foreach ([
            'admin.dashboard',
            'admin.agenda',
            'admin.agenda.show',
            'admin.agenda.confirmar',
            'admin.agenda.cancelar',
            'admin.agenda.concluir',
            'admin.disponibilidade.index',
        ] as $nome) {
            $middleware = Route::getRoutes()->getByName($nome)->gatherMiddleware();

            $this->assertContains('auth', $middleware, "Rota {$nome} deveria exigir autenticação.");
            $this->assertContains('can:access-admin-panel', $middleware, "Rota {$nome} deveria exigir o Gate de admin.");
            $this->assertContains('web', $middleware, "Rota {$nome} deveria estar no grupo web (CSRF).");
        }
    }

    public function test_visitante_e_redirecionado_ao_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk()->assertSee('Painel administrativo');
    }

    public function test_admin_autenticado_que_abre_o_login_vai_para_o_dashboard(): void
    {
        $this->actingAs($this->admin)->get('/admin/login')->assertRedirect('/admin');
        $this->actingAs($this->admin)->get('/admin')->assertOk()->assertSee('Solicitações pendentes');
    }

    public function test_login_e_logout_completos(): void
    {
        $this->admin->forceFill(['password' => 'senha-correta-123'])->save();

        $this->post('/admin/login', ['email' => $this->admin->email, 'password' => 'senha-correta-123'])
            ->assertRedirect('/admin');
        $this->assertAuthenticatedAs($this->admin);

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_rodape_ns_aparece_no_login_e_em_todas_as_telas_do_painel(): void
    {
        $copyright = '© '.now()->year.' NS | Nelson Schiavi Soluções Digitais. Todos os direitos reservados.';

        $this->get('/admin/login')->assertOk()->assertSee($copyright, false)->assertSee('Ambiente de demonstração');

        foreach (['/admin', '/admin/agenda', '/admin/disponibilidade'] as $url) {
            $this->actingAs($this->admin)->get($url)
                ->assertOk()
                ->assertSee($copyright, false)
                ->assertSee('Ambiente de demonstração')
                ->assertDontSee('Dra. Gabriela Ferri Paes. Todos os direitos reservados.', false)
                ->assertDontSee('Desenvolvido por');
        }
    }

    public function test_logo_oficial_em_todas_as_telas_e_nenhum_resquicio_do_provisorio(): void
    {
        $this->assertFileExists(public_path('imagens/logo-gabriela.png'));
        $this->assertFileExists(public_path('imagens/logo-gabriela.webp'));

        $consulta = $this->consulta(Appointment::STATUS_PENDING);
        $confirmacao = \Illuminate\Support\Facades\URL::temporarySignedRoute('agendamento.confirmado', now()->addHour(), ['appointment' => $consulta->id]);

        $publicas = ['/', '/agendamento', $confirmacao, '/admin/login'];
        foreach ($publicas as $url) {
            $this->get($url)->assertOk()
                ->assertSee('imagens/logo-gabriela.webp', false)
                ->assertDontSee('viewBox="0 0 48 44"', false); // SVG da marca provisória
        }

        foreach (['/admin', '/admin/agenda', '/admin/disponibilidade'] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk()
                ->assertSee('imagens/logo-gabriela.webp', false)
                ->assertDontSee('viewBox="0 0 48 44"', false);
        }
    }

    public function test_footer_tem_acesso_discreto_ao_painel_e_credito_ns(): void
    {
        $resposta = $this->get(route('home'));

        $resposta->assertSee('Área administrativa');
        $resposta->assertSee('href="'.url('/admin').'"', false);
        $resposta->assertSee('© '.now()->year.' NS | Nelson Schiavi Soluções Digitais. Todos os direitos reservados.', false);
        $resposta->assertDontSee('Dra. Gabriela Ferri Paes. Todos os direitos reservados.', false);
        $resposta->assertDontSee('Desenvolvido por');
    }

    public function test_dashboard_lista_pendentes_e_proximos(): void
    {
        $pendente = $this->consulta(Appointment::STATUS_PENDING);
        $outraData = $this->proximaSegundaFeira()->addDay();
        $confirmada = $this->consulta(Appointment::STATUS_CONFIRMED, $outraData);
        $confirmada->update(['nome_cliente' => 'Paciente Confirmado']);

        $resposta = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $resposta->assertOk();
        $resposta->assertSee('Solicitações pendentes');
        $resposta->assertSee('Próximos agendamentos');
        $resposta->assertSee($pendente->nome_cliente);
        $resposta->assertSee('Paciente Confirmado');
        $resposta->assertSee(route('admin.agenda.confirmar', $pendente), false);
    }

    public function test_admin_confirma_solicitacao_pendente(): void
    {
        $consulta = $this->consulta(Appointment::STATUS_PENDING);

        $this->actingAs($this->admin)->post(route('admin.agenda.confirmar', $consulta))->assertRedirect();

        $this->assertSame(Appointment::STATUS_CONFIRMED, $consulta->fresh()->status);
    }

    public function test_confirmar_agendamento_cancelado_e_rejeitado(): void
    {
        $consulta = $this->consulta(Appointment::STATUS_CANCELLED);

        $this->actingAs($this->admin)
            ->post(route('admin.agenda.confirmar', $consulta))
            ->assertSessionHasErrors('acao');

        $this->assertSame(Appointment::STATUS_CANCELLED, $consulta->fresh()->status);
    }

    public function test_admin_cancela_solicitacao_pendente_e_libera_o_horario(): void
    {
        $consulta = $this->consulta(Appointment::STATUS_PENDING);

        $this->actingAs($this->admin)->post(route('admin.agenda.cancelar', $consulta))->assertRedirect();

        $this->assertSame(Appointment::STATUS_CANCELLED, $consulta->fresh()->status);
        $resposta = $this->getJson(route('agendamento.horarios', [
            'tratamento_id' => $this->tratamento->id,
            'data' => $consulta->data->toDateString(),
        ]));
        $this->assertContains('10:00', array_column($resposta->json('horarios'), 'inicio'));
    }

    public function test_concluir_consulta_futura_e_rejeitado(): void
    {
        $consulta = $this->consulta(Appointment::STATUS_CONFIRMED);

        $this->actingAs($this->admin)
            ->post(route('admin.agenda.concluir', $consulta))
            ->assertSessionHasErrors('acao');

        $this->assertSame(Appointment::STATUS_CONFIRMED, $consulta->fresh()->status);
    }

    public function test_detalhe_traz_link_de_whatsapp_do_paciente(): void
    {
        $consulta = $this->consulta(Appointment::STATUS_PENDING);

        $this->actingAs($this->admin)
            ->getJson(route('admin.agenda.show', $consulta))
            ->assertJson([
                'tratamento' => $this->tratamento->nome,
                'whatsapp_url' => 'https://wa.me/5511988887777',
                'status_label' => 'Pendente',
            ]);
    }

    public function test_admin_altera_duracao_da_consulta(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.disponibilidade.duracao.atualizar'), ['duracao_consulta_min' => 30])
            ->assertRedirect();

        $this->assertSame(30, ConfiguracaoGlobal::atual()->duracao_consulta_min);
        $this->assertDatabaseHas('admin_logs', ['acao' => 'disponibilidade.duracao_consulta.atualizada']);
    }

    public function test_duracao_fora_da_lista_e_rejeitada(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.disponibilidade.duracao.atualizar'), ['duracao_consulta_min' => 7])
            ->assertSessionHasErrors('duracao_consulta_min');
    }

    // ---------------------------------------------------------------
    // Seeders
    // ---------------------------------------------------------------

    public function test_admin_so_e_criado_com_credenciais_do_ambiente(): void
    {
        config(['clinica.admin.email' => null, 'clinica.admin.senha' => null]);
        $this->seed(AdminUserSeeder::class);
        $this->assertDatabaseMissing('users', ['email' => 'admin@demo.test']);

        config(['clinica.admin.email' => 'admin@demo.test', 'clinica.admin.senha' => 'curta']);
        $this->seed(AdminUserSeeder::class);
        $this->assertDatabaseMissing('users', ['email' => 'admin@demo.test']);

        config(['clinica.admin.email' => 'admin@demo.test', 'clinica.admin.senha' => 'uma-senha-bem-longa-123']);
        $this->seed(AdminUserSeeder::class);
        $this->assertDatabaseHas('users', ['email' => 'admin@demo.test', 'role' => 'admin']);
    }

    public function test_seeder_de_tratamentos_cria_os_11_itens_informados(): void
    {
        $this->seed(TratamentoSeeder::class);

        $this->assertDatabaseHas('tratamentos', ['slug' => 'aparelho-invisivel']);
        $this->assertDatabaseHas('tratamentos', ['nome' => 'Avaliação / Não sei qual tratamento preciso']);
        // A fixture usa o mesmo slug "clareamento-dental": o seeder atualiza
        // (updateOrCreate) em vez de duplicar — total exato de 11.
        $this->assertSame(11, Tratamento::query()->count());
    }

    public function test_dados_de_demonstracao_sao_ficticios_e_idempotentes(): void
    {
        $this->seed(TratamentoSeeder::class);
        $this->seed(DemoAgendamentosSeeder::class);
        $this->seed(DemoAgendamentosSeeder::class);

        $demo = Appointment::query()->where('email_cliente', 'like', '%@demonstracao.invalid')->get();

        $this->assertCount(3, $demo);
        $this->assertTrue($demo->every(fn ($a) => str_starts_with($a->nome_cliente, 'Paciente Demonstração')));
        $this->assertEqualsCanonicalizing(
            [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED, Appointment::STATUS_COMPLETED],
            $demo->pluck('status')->all()
        );
    }
}
