<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // "Estar autenticado" não é sinônimo de "ter permissão administrativa"
        // o painel checa este Gate, não só o guard "web".
        // Hoje só existe o papel "admin" (users.role), mas o seam já fica
        // pronto para outros perfis: basta ajustar esta condição.
        Gate::define('access-admin-panel', function (User $user): bool {
            return $user->role === 'admin';
        });

        // Em produção (Render) o HTTPS termina no proxy: força https nas
        // URLs geradas, incluindo as assinadas da confirmação de agendamento.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Dados públicos da clínica disponíveis em todas as views.
        View::share('clinica', array_merge(config('clinica'), [
            'whatsapp_url' => 'https://wa.me/'.config('clinica.whatsapp_numero')
                .'?text='.rawurlencode(config('clinica.whatsapp_mensagem')),
            'mapa_url' => 'https://www.google.com/maps/search/?api=1&query='
                .rawurlencode('R. Biotônico, 1185 - Vila Colorado, Suzano - SP, 08616-040'),
            'mapa_embed' => 'https://www.google.com/maps?q='
                .rawurlencode('R. Biotônico, 1185 - Vila Colorado, Suzano - SP, 08616-040').'&output=embed',
            'admin' => null, // nunca expor credenciais às views
        ]));
    }
}
