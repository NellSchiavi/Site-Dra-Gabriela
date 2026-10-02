<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Cria/atualiza o único acesso administrativo da demo a partir das
 * variáveis de ambiente ADMIN_EMAIL / ADMIN_PASSWORD (via config/clinica.php).
 * Nenhuma credencial fica escrita em código: sem as variáveis, nada é criado.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('clinica.admin.email');
        $senha = config('clinica.admin.senha');

        if (blank($email) || blank($senha)) {
            $this->command?->warn('ADMIN_EMAIL/ADMIN_PASSWORD não definidos — usuário administrativo não foi criado.');

            return;
        }

        if (mb_strlen($senha) < 12) {
            $this->command?->error('ADMIN_PASSWORD precisa ter pelo menos 12 caracteres — usuário administrativo não foi criado.');

            return;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => config('clinica.admin.nome'),
                'password' => $senha,
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
    }
}
