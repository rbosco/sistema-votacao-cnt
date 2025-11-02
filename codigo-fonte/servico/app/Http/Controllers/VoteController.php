<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VoteController extends Controller
{
    /**
     * Registra um voto
     */
    public function vote(Request $request)
    {
        $request->validate([
            'cpf' => 'required|string|size:11',
            'name' => 'required|string|max:255',
            'union_name' => 'required|string|max:255',
            'vote' => 'required|boolean',
        ]);

        // Verifica se existe uma proposta ativa
        $proposal = Proposal::active()->first();

        if (!$proposal) {
            return response()->json([
                'message' => 'Votação encerrada.',
                'status' => 'closed'
            ], 422);
        }

        // Verifica se o CPF já votou nesta proposta
        $existingVote = Vote::where('proposal_id', $proposal->id)
            ->where('voter_cpf', $request->cpf)
            ->first();

        if ($existingVote) {
            throw ValidationException::withMessages([
                'cpf' => ['Você já votou nesta proposta.'],
            ]);
        }

        // Registra o voto
        $vote = Vote::create([
            'proposal_id' => $proposal->id,
            'voter_cpf' => $request->cpf,
            'voter_name' => $request->name,
            'union_name' => $request->union_name,
            'vote' => $request->vote,
            'voted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Voto registrado com sucesso!',
            'vote' => $vote,
        ], 201);
    }

    /**
     * Lista todos os votos (admin)
     */
    public function index()
    {
        $votes = Vote::with('proposal')
            ->orderBy('voted_at', 'desc')
            ->get();

        return response()->json($votes);
    }

    /**
     * Lista os votos de uma proposta específica
     */
    public function getByProposal($id)
    {
        $votes = Vote::where('proposal_id', $id)
            ->orderBy('voted_at', 'desc')
            ->get();

        return response()->json($votes);
    }
}
