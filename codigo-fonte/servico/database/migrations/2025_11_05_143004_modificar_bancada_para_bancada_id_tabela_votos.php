<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Adicionar coluna bancada_id
        Schema::table('votos', function (Blueprint $table) {
            $table->unsignedBigInteger('bancada_id')->nullable()->after('cpf_votante');
        });

        // Migrar dados existentes de bancada (string) para bancada_id
        DB::table('votos')->whereNotNull('bancada')->update([
            'bancada_id' => DB::raw("CASE
                WHEN bancada = 'Trabalhadores' THEN 1
                WHEN bancada = 'Empregadores' THEN 2
                WHEN bancada = 'Governo' THEN 3
                ELSE NULL
            END")
        ]);

        // Remover coluna bancada antiga e adicionar foreign key
        Schema::table('votos', function (Blueprint $table) {
            $table->dropColumn('bancada');

            $table->foreign('bancada_id')
                  ->references('id')
                  ->on('bancadas')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover foreign key
        Schema::table('votos', function (Blueprint $table) {
            $table->dropForeign(['bancada_id']);
        });

        // Adicionar coluna bancada de volta
        Schema::table('votos', function (Blueprint $table) {
            $table->string('bancada', 20)->nullable()->after('cpf_votante');
        });

        // Migrar dados de volta para string
        DB::table('votos')->whereNotNull('bancada_id')->update([
            'bancada' => DB::raw("CASE
                WHEN bancada_id = 1 THEN 'Trabalhadores'
                WHEN bancada_id = 2 THEN 'Empregadores'
                WHEN bancada_id = 3 THEN 'Governo'
                ELSE NULL
            END")
        ]);

        // Remover coluna bancada_id
        Schema::table('votos', function (Blueprint $table) {
            $table->dropColumn('bancada_id');
        });
    }
};
