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
        Schema::create('bancadas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50)->unique();
            $table->string('sigla', 10)->nullable();
            $table->timestamps();
        });

        // Inserir as 3 bancadas
        DB::table('bancadas')->insert([
            ['nome' => 'Trabalhadores', 'sigla' => 'TRAB', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Empregadores', 'sigla' => 'EMPR', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Governo', 'sigla' => 'GOV', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bancadas');
    }
};
