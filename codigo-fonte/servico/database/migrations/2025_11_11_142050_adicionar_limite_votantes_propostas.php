<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            // Limite de votantes para a proposta
            $table->integer('limite_votantes')->nullable()->after('status');

            // Temporizador movido de configurações para propostas
            $table->datetime('temporizador_inicio')->nullable()->after('limite_votantes');
            $table->integer('temporizador_duracao_minutos')->nullable()->after('temporizador_inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            $table->dropColumn(['limite_votantes', 'temporizador_inicio', 'temporizador_duracao_minutos']);
        });
    }
};
