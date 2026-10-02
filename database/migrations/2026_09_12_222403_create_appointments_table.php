<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tratamento_id')
                ->constrained('tratamentos')
                ->restrictOnDelete();
            $table->string('nome_cliente', 150);
            $table->string('email_cliente', 190);
            $table->string('telefone_cliente', 30);
            $table->date('data');
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])
                ->default('pending');
            $table->string('cancelled_reason', 255)->nullable();
            $table->timestamps();

            $table->index('email_cliente');
            $table->index('status');
            // Suporte à checagem de sobreposição por intervalo — mecanismo
            // principal de concorrência (ver AppointmentService).
            $table->index(['data', 'status', 'hora_inicio', 'hora_fim']);
        });

        // Rede de segurança secundária de concorrência: no máximo um
        // agendamento ATIVO (pendente/confirmado) por data+hora de início.
        // Índice único parcial (PostgreSQL/SQLite) — um cancelado não ocupa
        // o horário e não bloqueia uma nova reserva.
        DB::statement(
            "CREATE UNIQUE INDEX appointments_active_slot_unique ON appointments (data, hora_inicio) WHERE status IN ('pending', 'confirmed')"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
