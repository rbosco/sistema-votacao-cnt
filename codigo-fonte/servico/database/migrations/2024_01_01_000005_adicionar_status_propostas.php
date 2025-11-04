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
        Schema::table('propostas', function (Blueprint $table) {
            $table->enum('status', ['em_votacao', 'encerrada'])
                ->default('em_votacao')
                ->after('esta_ativa');

            $table->index('status');
        });
    }

    /**
     * Reverter as migrations
     */
    public function down(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
