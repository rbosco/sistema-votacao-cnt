<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProposalController extends Controller
{
    /**
     * Lista todas as propostas
     */
    public function index()
    {
        $proposals = Proposal::withCount([
            'votes',
            'votes as yes_votes_count' => function ($query) {
                $query->where('vote', true);
            },
            'votes as no_votes_count' => function ($query) {
                $query->where('vote', false);
            }
        ])->orderBy('created_at', 'desc')->get();

        return response()->json($proposals);
    }

    /**
     * Retorna a proposta ativa
     */
    public function getActive()
    {
        $proposal = Proposal::active()->first();

        return response()->json($proposal);
    }

    /**
     * Cria uma nova proposta
     */
    public function store(Request $request)
    {
        $request->validate([
            'number' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
        ]);

        $proposal = Proposal::create([
            'number' => $request->number,
            'name' => $request->name,
            'is_active' => false,
        ]);

        return response()->json($proposal, 201);
    }

    /**
     * Atualiza uma proposta
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'number' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
        ]);

        $proposal = Proposal::findOrFail($id);
        $proposal->update([
            'number' => $request->number,
            'name' => $request->name,
        ]);

        return response()->json($proposal);
    }

    /**
     * Remove uma proposta
     */
    public function destroy($id)
    {
        $proposal = Proposal::findOrFail($id);
        $proposal->delete();

        return response()->json([
            'message' => 'Proposta removida com sucesso.',
        ]);
    }

    /**
     * Ativa uma proposta (desativa as demais)
     */
    public function activate($id)
    {
        DB::transaction(function () use ($id) {
            // Desativa todas as propostas
            Proposal::where('is_active', true)->update(['is_active' => false]);

            // Ativa a proposta selecionada
            $proposal = Proposal::findOrFail($id);
            $proposal->update(['is_active' => true]);
        });

        return response()->json([
            'message' => 'Proposta ativada com sucesso.',
        ]);
    }

    /**
     * Desativa uma proposta
     */
    public function deactivate($id)
    {
        $proposal = Proposal::findOrFail($id);
        $proposal->update(['is_active' => false]);

        return response()->json([
            'message' => 'Proposta desativada com sucesso.',
        ]);
    }

    /**
     * Retorna os resultados de uma proposta
     */
    public function getResults($encryptedId)
    {
        try {
            $id = decrypt($encryptedId);
            $proposal = Proposal::with(['votes' => function ($query) {
                $query->orderBy('voted_at', 'desc');
            }])->findOrFail($id);

            $results = [
                'proposal' => $proposal,
                'total_votes' => $proposal->votes->count(),
                'yes_votes' => $proposal->votes->where('vote', true)->count(),
                'no_votes' => $proposal->votes->where('vote', false)->count(),
                'votes' => $proposal->votes,
            ];

            return response()->json($results);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Proposta não encontrada.',
            ], 404);
        }
    }
}
