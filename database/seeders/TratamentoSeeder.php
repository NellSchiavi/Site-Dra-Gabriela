<?php

namespace Database\Seeders;

use App\Models\Tratamento;
use Illuminate\Database\Seeder;

/**
 * Tratamentos informados pela Dra. Gabriela (mais a opção de avaliação para
 * quem não sabe qual tratamento precisa). As descrições são deliberadamente
 * neutras: dizem o que é cada procedimento, sem prometer resultado.
 */
class TratamentoSeeder extends Seeder
{
    public function run(): void
    {
        $tratamentos = [
            ['aparelho-invisivel', 'Aparelho Invisível', 'Alinhadores transparentes para o alinhamento dos dentes.', 'alinhador'],
            ['clareamento-dental', 'Clareamento Dental', 'Procedimento para clarear a tonalidade dos dentes.', 'brilho'],
            ['estetica', 'Estética', 'Cuidados voltados à harmonia e à aparência do sorriso.', 'sorriso'],
            ['restauracoes', 'Restaurações', 'Recuperação de dentes afetados por cáries, fraturas ou desgastes.', 'dente'],
            ['raspagem', 'Raspagem', 'Remoção de tártaro e placa bacteriana para o cuidado da gengiva.', 'gota'],
            ['lentes-de-resina', 'Lentes de Resina', 'Facetas em resina para ajustes de forma e cor dos dentes.', 'camadas'],
            ['extracoes', 'Extrações', 'Remoção de dentes, quando indicada após avaliação clínica.', 'extracao'],
            ['protese', 'Prótese', 'Reabilitação com próteses para substituir dentes ausentes.', 'coroa'],
            ['atendimento-infantil', 'Atendimento Infantil', 'Cuidado odontológico para crianças.', 'crianca'],
            ['atendimento-adulto', 'Atendimento Adulto', 'Cuidado odontológico para adultos.', 'adulto'],
            ['avaliacao', 'Avaliação / Não sei qual tratamento preciso', 'Uma consulta de avaliação para entender o que é indicado para você.', 'lupa'],
        ];

        foreach ($tratamentos as $ordem => [$slug, $nome, $descricao, $icone]) {
            Tratamento::query()->updateOrCreate(['slug' => $slug], [
                'nome' => $nome,
                'descricao' => $descricao,
                'icone' => $icone,
                'ordem' => $ordem + 1,
                'ativo' => true,
            ]);
        }
    }
}
