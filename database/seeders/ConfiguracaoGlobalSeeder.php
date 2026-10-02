<?php

namespace Database\Seeders;

use App\Models\ConfiguracaoGlobal;
use Illuminate\Database\Seeder;

class ConfiguracaoGlobalSeeder extends Seeder
{
    public function run(): void
    {
        $config = ConfiguracaoGlobal::atual();

        // Só preenche na primeira execução: o seed roda a cada deploy e não
        // deve sobrescrever uma duração já ajustada pelo painel.
        if ($config->wasRecentlyCreated) {
            $config->fill([
                'duracao_consulta_min' => 60,
                'intervalo_entre_consultas_min' => 0,
                'antecedencia_minima_horas' => 2,
                'antecedencia_maxima_dias' => 60,
                // Toda solicitação pública entra como "Pendente" e é
                // confirmada pelo painel.
                'modo_confirmacao' => ConfiguracaoGlobal::MODO_MANUAL,
            ])->save();
        }
    }
}
