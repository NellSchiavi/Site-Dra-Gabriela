<?php

namespace Database\Seeders;

use App\Models\AvailabilityRule;
use Illuminate\Database\Seeder;

/**
 * Horários PROVISÓRIOS da demonstração (a Dra. ainda não informou os reais):
 * segunda a sexta 09:00–18:00, sábado 09:00–13:00, domingo fechado.
 * Configuráveis pelo painel em /admin/disponibilidade e nunca exibidos no
 * site público como horário oficial.
 *
 * firstOrCreate: o seed roda a cada boot no Render — nunca sobrescreve um
 * horário já ajustado pelo painel.
 */
class AvailabilityRuleSeeder extends Seeder
{
    public function run(): void
    {
        // 0 = domingo ... 6 = sábado (convenção do Carbon/dayOfWeek)
        foreach ([1, 2, 3, 4, 5] as $dia) {
            AvailabilityRule::query()->firstOrCreate(
                ['day_of_week' => $dia],
                ['hora_inicio' => '09:00:00', 'hora_fim' => '18:00:00', 'ativo' => true]
            );
        }

        AvailabilityRule::query()->firstOrCreate(
            ['day_of_week' => 6],
            ['hora_inicio' => '09:00:00', 'hora_fim' => '13:00:00', 'ativo' => true]
        );
    }
}
