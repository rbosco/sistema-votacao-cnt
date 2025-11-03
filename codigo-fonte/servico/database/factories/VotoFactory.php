<?php

namespace Database\Factories;

use App\Models\Voto;
use App\Models\Proposta;
use Illuminate\Database\Eloquent\Factories\Factory;

class VotoFactory extends Factory
{
    protected $model = Voto::class;

    public function definition(): array
    {
        return [
            'proposta_id' => Proposta::factory(),
            'cpf_votante' => $this->faker->numerify('###########'),
            'voto' => $this->faker->boolean(),
            'votado_em' => now(),
        ];
    }
}
