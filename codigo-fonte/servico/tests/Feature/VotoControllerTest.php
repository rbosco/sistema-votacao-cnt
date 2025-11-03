<?php

namespace Tests\Feature;

use App\Models\Proposta;
use App\Models\Voto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VotoControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function deve_registrar_voto_sim()
    {
        // Arrange
        $proposta = Proposta::factory()->create(['esta_ativa' => true]);

        // Act
        $response = $this->postJson('/api/votar', [
            'cpf_votante' => '12345678901',
            'voto' => 1,
            'proposta_id' => $proposta->id,
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('votos', [
            'cpf_votante' => '12345678901',
            'voto' => 1,  // Sim = 1
            'proposta_id' => $proposta->id,
        ]);
    }

    /** @test */
    public function deve_registrar_voto_nao()
    {
        // Arrange
        $proposta = Proposta::factory()->create(['esta_ativa' => true]);

        // Act
        $response = $this->postJson('/api/votar', [
            'cpf_votante' => '12345678901',
            'voto' => 0,
            'proposta_id' => $proposta->id,
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('votos', [
            'cpf_votante' => '12345678901',
            'voto' => 0,  // Não = 0
            'proposta_id' => $proposta->id,
        ]);
    }

    /** @test */
    public function nao_deve_permitir_voto_duplicado()
    {
        // Arrange
        $proposta = Proposta::factory()->create(['esta_ativa' => true]);
        Voto::factory()->create([
            'cpf_votante' => '12345678901',
            'proposta_id' => $proposta->id,
        ]);

        // Act
        $response = $this->postJson('/api/votar', [
            'cpf_votante' => '12345678901',
            'voto' => 1,
            'proposta_id' => $proposta->id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cpf_votante']);
    }

    /** @test */
    public function nao_deve_permitir_voto_em_proposta_inativa()
    {
        // Arrange
        $proposta = Proposta::factory()->create(['esta_ativa' => false]);

        // Act
        $response = $this->postJson('/api/votar', [
            'cpf_votante' => '12345678901',
            'voto' => 1,
            'proposta_id' => $proposta->id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJson(['message' => 'Votação encerrada.']);
    }

    /** @test */
    public function deve_validar_cpf_obrigatorio()
    {
        // Arrange
        $proposta = Proposta::factory()->create(['esta_ativa' => true]);

        // Act
        $response = $this->postJson('/api/votar', [
            'voto' => 1,
            'proposta_id' => $proposta->id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cpf_votante']);
    }

    /** @test */
    public function deve_validar_voto_obrigatorio()
    {
        // Arrange
        $proposta = Proposta::factory()->create(['esta_ativa' => true]);

        // Act
        $response = $this->postJson('/api/votar', [
            'cpf_votante' => '12345678901',
            'proposta_id' => $proposta->id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['voto']);
    }
}
