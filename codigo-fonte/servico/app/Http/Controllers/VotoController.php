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
            'cpf' => 'required|string|size:11',
            'nome' => 'required|string|max:255',
            'nome_sindicato' => 'required|string|max:255',
            'voto' => 'required|boolean',
        ]);

        // Verificar se existe uma proposta ativa
        $proposta = Proposta::ativa()->first();

        if (!$proposta) {
            return response()->json([
                'mensagem' => 'Votação encerrada.',
                'status' => 'encerrada'
            ], 422);
        }

        // Verificar se o CPF já votou nesta proposta
        $votoExistente = Voto::where('proposta_id', $proposta->id)
            ->where('cpf_votante', $request->cpf)
            ->first();

        if ($votoExistente) {
            throw ValidationException::withMessages([
                'cpf' => ['Você já votou nesta proposta.'],
            ]);
        }

        // Registrar o voto
        $voto = Voto::create([
            'proposta_id' => $proposta->id,
            'cpf_votante' => $request->cpf,
            'nome_votante' => $request->nome,
            'nome_sindicato' => $request->nome_sindicato,
            'voto' => $request->voto,
            'votado_em' => now(),
        ]);

        return response()->json([
            'mensagem' => 'Voto registrado com sucesso!',
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
