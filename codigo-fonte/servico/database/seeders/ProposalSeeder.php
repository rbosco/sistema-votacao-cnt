<?php

namespace Database\Seeders;

use App\Models\Proposal;
use Illuminate\Database\Seeder;

class ProposalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Proposal::create([
            'number' => '001/2024',
            'name' => 'Proposta de Aumento Salarial de 10%',
            'is_active' => false,
        ]);

        Proposal::create([
            'number' => '002/2024',
            'name' => 'Ampliação do Plano de Saúde',
            'is_active' => false,
        ]);

        Proposal::create([
            'number' => '003/2024',
            'name' => 'Redução da Jornada de Trabalho',
            'is_active' => false,
        ]);

        Proposal::create([
            'number' => null,
            'name' => 'Proposta de Vale-Alimentação',
            'is_active' => false,
        ]);
    }
}
