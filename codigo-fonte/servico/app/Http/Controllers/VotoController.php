<?php

namespace App\Http\Controllers;

use App\Models\Proposta;
use App\Models\Voto;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VotoController extends Controller
{
    /**
     * Registrar um voto
     */
    public function votar(Request $request)
    {
        $request->validate([
            'cpf_votante' => 'required|string|size:11',
            'voto' => 'required|in:0,1',
            'proposta_id' => 'required|exists:propostas,id',
        ], [
            'cpf_votante.required' => 'O CPF é obrigatório.',
            'cpf_votante.size' => 'O CPF deve ter 11 dígitos.',
            'voto.required' => 'O voto é obrigatório.',
            'voto.in' => 'O voto deve ser 0 (Não) ou 1 (Sim).',
            'proposta_id.required' => 'A proposta é obrigatória.',
            'proposta_id.exists' => 'A proposta selecionada não existe.',
        ]);

        // Verificar se a proposta está ativa
        $proposta = Proposta::find($request->proposta_id);

        if (!$proposta || !$proposta->esta_ativa) {
            return response()->json([
                'message' => 'Votação encerrada.',
            ], 422);
        }

        // Verificar se o CPF já votou nesta proposta
        $votoExistente = Voto::where('proposta_id', $proposta->id)
            ->where('cpf_votante', $request->cpf_votante)
            ->first();

        if ($votoExistente) {
            throw ValidationException::withMessages([
                'cpf_votante' => ['Você já votou nesta proposta.'],
            ]);
        }

        // Registrar o voto (0 = Não, 1 = Sim)
        $voto = Voto::create([
            'proposta_id' => $proposta->id,
            'cpf_votante' => $request->cpf_votante,
            'voto' => (int) $request->voto,
            'votado_em' => now(),
        ]);

        return response()->json([
            'message' => 'Voto registrado com sucesso!',
            'voto' => $voto,
        ], 201);
    }

    /**
     * Listar todos os votos (admin)
     */
    public function index()
    {
        $votos = Voto::with('proposta')
            ->orderBy('votado_em', 'desc')
            ->get();

        return response()->json($votos);
    }

    /**
     * Listar os votos de uma proposta específica
     */
    public function obterPorProposta($id)
    {
        $votos = Voto::where('proposta_id', $id)
            ->orderBy('votado_em', 'desc')
            ->get();

        return response()->json($votos);
    }
}
