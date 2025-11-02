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
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->onDelete('cascade');
            $table->string('voter_cpf', 11);
            $table->string('voter_name');
            $table->string('union_name');
            $table->boolean('vote'); // true = Sim, false = Não
            $table->timestamp('voted_at');
            $table->timestamps();

            $table->unique(['proposal_id', 'voter_cpf']);
            $table->index('proposal_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
