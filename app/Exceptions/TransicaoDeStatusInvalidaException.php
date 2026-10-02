<?php

namespace App\Exceptions;

use App\Models\Appointment;
use RuntimeException;

/**
 * Lançada quando uma ação administrativa (confirmar, cancelar, concluir) é
 * tentada num agendamento cujo status atual não permite essa transição —
 * ex.: cancelar uma consulta já concluída. Garante que o estado real do
 * banco (possivelmente alterado por outra requisição concorrente) nunca é
 * sobrescrito silenciosamente.
 */
class TransicaoDeStatusInvalidaException extends RuntimeException
{
    public static function paraAcao(string $acao, string $statusAtual): self
    {
        $rotulo = Appointment::STATUS_LABELS[$statusAtual] ?? $statusAtual;

        return new self("Não é possível {$acao} este agendamento porque o status atual é \"{$rotulo}\".");
    }

    /**
     * COMPLETED só faz sentido depois que o horário da consulta realmente
     * terminou — marcar uma consulta futura como concluída liberaria o
     * horário prematuramente no AvailabilityService.
     */
    public static function paraConsultaNaoEncerrada(string $descricaoDaAcao): self
    {
        return new self("Esta consulta ainda não terminou e não pode ser {$descricaoDaAcao}.");
    }
}
