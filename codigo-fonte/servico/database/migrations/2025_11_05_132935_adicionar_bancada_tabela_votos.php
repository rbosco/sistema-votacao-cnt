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
        Schema::table('votos', function (Blueprint $table) {
            $table->string('bancada', 20)->nullable()->after('cpf_votante');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votos', function (Blueprint $table) {
            $table->dropColumn('bancada');
        });
    }
};
