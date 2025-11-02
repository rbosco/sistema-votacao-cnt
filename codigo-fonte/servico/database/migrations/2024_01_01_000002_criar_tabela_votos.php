<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executar as migrations
     */
    public function up(): void
    {
        Schema::create('votos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposta_id')->constrained('propostas')->onDelete('cascade');
            $table->string('cpf_votante', 11);
            $table->string('nome_votante');
            $table->string('nome_sindicato');
            $table->boolean('voto'); // true = Sim, false = Não
            $table->timestamp('votado_em');
            $table->timestamps();

            $table->unique(['proposta_id', 'cpf_votante']);
            $table->index('proposta_id');
        });
    }

    /**
     * Reverter as migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('votos');
    }
};
