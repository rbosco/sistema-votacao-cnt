<?php

namespace Database\Seeders;

use App\Models\Proposta;
use Illuminate\Database\Seeder;

class PropostaSeeder extends Seeder
{
    /**
     * Executar os seeds do banco de dados
     */
    public function run(): void
    {
        Proposta::create([
            'numero' => '001/2024',
            'nome' => 'Proposta de Aumento Salarial de 10%',
            'esta_ativa' => false,
        ]);

        Proposta::create([
            'numero' => '002/2024',
            'nome' => 'Ampliação do Plano de Saúde',
            'esta_ativa' => false,
        ]);

        Proposta::create([
            'numero' => '003/2024',
            'nome' => 'Redução da Jornada de Trabalho',
            'esta_ativa' => false,
        ]);

        Proposta::create([
            'numero' => null,
            'nome' => 'Proposta de Vale-Alimentação',
            'esta_ativa' => false,
        ]);
    }
}
