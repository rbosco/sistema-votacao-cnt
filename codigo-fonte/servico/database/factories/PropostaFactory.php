<?php

namespace Database\Factories;

use App\Models\Proposta;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropostaFactory extends Factory
{
    protected $model = Proposta::class;

    public function definition(): array
    {
        return [
            'numero' => $this->faker->numerify('###'),
            'nome' => $this->faker->sentence(),
            'esta_ativa' => false,
        ];
    }

    public function ativa(): static
    {
        return $this->state(fn (array $attributes) => [
            'esta_ativa' => true,
        ]);
    }
}
