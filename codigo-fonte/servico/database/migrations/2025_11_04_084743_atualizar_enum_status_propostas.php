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
        // Para SQL Server, precisamos recriar a coluna
        Schema::table('propostas', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('propostas', function (Blueprint $table) {
            $table->enum('status', ['nao_iniciada', 'em_votacao', 'encerrada'])
                ->default('nao_iniciada')
                ->after('esta_ativa');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('propostas', function (Blueprint $table) {
            $table->enum('status', ['em_votacao', 'encerrada'])
                ->default('em_votacao')
                ->after('esta_ativa');
            $table->index('status');
        });
    }
};
