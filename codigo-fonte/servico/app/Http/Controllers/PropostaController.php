<?php

namespace App\Http\Controllers;

use App\Models\Proposta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropostaController extends Controller
{
    /**
     * Listar todas as propostas
     */
    public function index()
    {
        $propostas = Proposta::withCount([
            'votos',
            'votos as total_votos_sim' => function ($query) {
                $query->where('voto', true);
            },
            'votos as total_votos_nao' => function ($query) {
                $query->where('voto', false);
            }
        ])->orderBy('created_at', 'desc')->get();

        return response()->json($propostas);
    }

    /**
     * Retornar a proposta ativa
     */
    public function obterAtiva()
    {
        $proposta = Proposta::ativa()->first();

        return response()->json($proposta);
    }

    /**
     * Criar uma nova proposta
     */
    public function armazenar(Request $request)
    {
        $request->validate([
            'numero' => 'nullable|string|max:255',
            'nome' => 'required|string|max:255',
        ], [
            'nome.required' => 'O nome da proposta é obrigatório.',
            'nome.max' => 'O nome não pode ter mais de :max caracteres.',
            'numero.max' => 'O número não pode ter mais de :max caracteres.',
        ]);

        $proposta = Proposta::create([
            'numero' => $request->numero,
            'nome' => $request->nome,
            'esta_ativa' => false,
        ]);

        return response()->json($proposta, 201);
    }

    /**
     * Atualizar uma proposta
     */
    public function atualizar(Request $request, $id)
    {
        $request->validate([
            'numero' => 'nullable|string|max:255',
            'nome' => 'required|string|max:255',
            'esta_ativa' => 'boolean',
        ], [
            'nome.required' => 'O nome da proposta é obrigatório.',
            'nome.max' => 'O nome não pode ter mais de :max caracteres.',
            'numero.max' => 'O número não pode ter mais de :max caracteres.',
        ]);

        $proposta = Proposta::findOrFail($id);

        // Se a proposta está sendo marcada como ativa
        if ($request->has('esta_ativa') && $request->esta_ativa) {
            DB::transaction(function () use ($request, $proposta) {
                // Desativar todas as propostas
                Proposta::where('esta_ativa', true)->update(['esta_ativa' => false]);

                // Atualizar a proposta e marcá-la como ativa
                $proposta->update([
                    'numero' => $request->numero,
                    'nome' => $request->nome,
                    'esta_ativa' => true,
                ]);
            });
        } else {
            // Atualizar normalmente
            $proposta->update([
                'numero' => $request->numero,
                'nome' => $request->nome,
                'esta_ativa' => $request->esta_ativa ?? $proposta->esta_ativa,
            ]);
        }

        return response()->json($proposta);
    }

    /**
     * Remover uma proposta
     */
    public function destruir($id)
    {
        $proposta = Proposta::findOrFail($id);
        $proposta->delete();

        return response()->json([
            'mensagem' => 'Proposta removida com sucesso.',
        ]);
    }

    /**
     * Ativar uma proposta (desativar todas as outras)
     */
    public function ativar($id)
    {
        DB::transaction(function () use ($id) {
            // Desativar todas as propostas
            Proposta::where('esta_ativa', true)->update(['esta_ativa' => false]);

            // Ativar a proposta selecionada
            $proposta = Proposta::findOrFail($id);
            $proposta->update(['esta_ativa' => true]);
        });

        return response()->json([
            'mensagem' => 'Proposta ativada com sucesso.',
        ]);
    }

    /**
     * Desativar uma proposta
     */
    public function desativar($id)
    {
        $proposta = Proposta::findOrFail($id);
        $proposta->update(['esta_ativa' => false]);

        return response()->json([
            'mensagem' => 'Proposta desativada com sucesso.',
        ]);
    }

    /**
     * Retornar os resultados de uma proposta
     */
    public function obterResultados($idCriptografado)
    {
        try {
            $id = decrypt($idCriptografado);
            $proposta = Proposta::with(['votos' => function ($query) {
                $query->orderBy('votado_em', 'desc');
            }])->findOrFail($id);

            $resultados = [
                'proposta' => $proposta,
                'total_votos' => $proposta->votos->count(),
                'votos_sim' => $proposta->votos->where('voto', true)->count(),
                'votos_nao' => $proposta->votos->where('voto', false)->count(),
                'votos' => $proposta->votos,
            ];

            return response()->json($resultados);
        } catch (\Exception $e) {
            return response()->json([
                'mensagem' => 'Proposta não encontrada.',
            ], 404);
        }
    }
}
