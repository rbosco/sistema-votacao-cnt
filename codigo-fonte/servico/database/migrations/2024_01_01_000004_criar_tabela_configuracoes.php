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
        Schema::create('configuracoes', function (Blueprint $table) {
            $table->id();
            $table->string('chave')->unique();
            $table->text('valor')->nullable();
            $table->timestamps();
        });

        // Inserir configurações padrão
        DB::table('configuracoes')->insert([
            [
                'chave' => 'banner_votacao',
                'valor' => '/images/banner-cnt.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'chave' => 'temporizador_ativo',
                'valor' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'chave' => 'temporizador_inicio',
                'valor' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'chave' => 'temporizador_duracao_minutos',
                'valor' => '30',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracoes');
    }
};
