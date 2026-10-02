<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Tratamento;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Poucos registros CLARAMENTE FICTÍCIOS para o painel não aparecer vazio na
 * apresentação: 1 pendente, 1 confirmado, 1 concluído. Nomes "Paciente
 * Demonstração", e-mails no domínio reservado ".invalid" e telefones
 * impossíveis (DDD 00) — nenhum dado de pessoa real.
 *
 * Idempotente: só cria se ainda não existir nenhum registro de demonstração.
 */
class DemoAgendamentosSeeder extends Seeder
{
    public function run(): void
    {
        if (Appointment::query()->where('email_cliente', 'like', '%@demonstracao.invalid')->exists()) {
            return;
        }

        $tratamentos = Tratamento::query()->pluck('id', 'slug');

        if ($tratamentos->isEmpty()) {
            return;
        }

        $hoje = CarbonImmutable::now()->startOfDay();

        $registros = [
            [
                'nome' => 'Paciente Demonstração 1',
                'tratamento' => 'clareamento-dental',
                'data' => $this->proximoDiaUtil($hoje->addDays(2)),
                'hora' => '10:00',
                'status' => Appointment::STATUS_PENDING,
            ],
            [
                'nome' => 'Paciente Demonstração 2',
                'tratamento' => 'avaliacao',
                'data' => $this->proximoDiaUtil($hoje->addDay()),
                'hora' => '11:00', // dentro do expediente provisório também aos sábados
                'status' => Appointment::STATUS_CONFIRMED,
            ],
            [
                'nome' => 'Paciente Demonstração 3',
                'tratamento' => 'restauracoes',
                'data' => $this->diaUtilAnterior($hoje->subDay()),
                'hora' => '09:00',
                'status' => Appointment::STATUS_COMPLETED,
            ],
        ];

        foreach ($registros as $indice => $registro) {
            $inicio = CarbonImmutable::createFromFormat('H:i', $registro['hora']);

            $appointment = new Appointment([
                'tratamento_id' => $tratamentos[$registro['tratamento']] ?? $tratamentos->first(),
                'nome_cliente' => $registro['nome'],
                'email_cliente' => 'paciente'.($indice + 1).'@demonstracao.invalid',
                'telefone_cliente' => '(00) 00000-000'.($indice + 1),
                'data' => $registro['data']->toDateString(),
                'hora_inicio' => $inicio->format('H:i'),
                'hora_fim' => $inicio->addHour()->format('H:i'),
            ]);
            $appointment->status = $registro['status'];
            $appointment->save();
        }
    }

    /** Segunda a sábado (domingo = consultório fechado). */
    private function proximoDiaUtil(CarbonImmutable $data): CarbonImmutable
    {
        return $data->isSunday() ? $data->addDay() : $data;
    }

    private function diaUtilAnterior(CarbonImmutable $data): CarbonImmutable
    {
        return $data->isSunday() ? $data->subDay() : $data;
    }
}
