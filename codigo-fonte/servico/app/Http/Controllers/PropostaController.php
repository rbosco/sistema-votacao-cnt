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
                $query->where('voto', 1);
            },
            'votos as total_votos_nao' => function ($query) {
                $query->where('voto', 0);
            }
        ])->orderBy('created_at', 'desc')->get();

        // Adicionar ID criptografado
        $propostas->transform(function ($proposta) {
            $proposta->id_criptografado = encrypt($proposta->id);
            return $proposta;
        });

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
        try {
            \Log::info('Iniciando atualização de proposta', [
                'proposta_id' => $id,
                'dados' => $request->all()
            ]);

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
            \Log::info('Proposta encontrada', ['proposta' => $proposta->toArray()]);

            // Se a proposta está sendo marcada como ativa
            if ($request->has('esta_ativa') && $request->esta_ativa) {
                \Log::info('Ativando proposta', ['proposta_id' => $id]);

                DB::transaction(function () use ($request, $id) {
                    // Desativar todas as propostas
                    $desativadas = Proposta::where('esta_ativa', true)->update(['esta_ativa' => false]);
                    \Log::info('Propostas desativadas', ['count' => $desativadas]);

                    // Atualizar a proposta e marcá-la como ativa
                    $proposta = Proposta::findOrFail($id);
                    $proposta->update([
                        'numero' => $request->numero,
                        'nome' => $request->nome,
                        'esta_ativa' => true,
                    ]);
                    \Log::info('Proposta atualizada e ativada', ['proposta' => $proposta->toArray()]);
                });

                // Recarregar a proposta para retornar os dados atualizados
                $proposta = Proposta::findOrFail($id);
            } else {
                \Log::info('Atualizando proposta normalmente', [
                    'proposta_id' => $id,
                    'esta_ativa' => $request->esta_ativa ?? $proposta->esta_ativa
                ]);

                // Atualizar normalmente
                $proposta->update([
                    'numero' => $request->numero,
                    'nome' => $request->nome,
                    'esta_ativa' => $request->esta_ativa ?? $proposta->esta_ativa,
                ]);
            }

            \Log::info('Proposta atualizada com sucesso', ['proposta' => $proposta->toArray()]);
            return response()->json($proposta);

        } catch (\Exception $e) {
            \Log::error('Erro ao atualizar proposta', [
                'proposta_id' => $id,
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erro ao atualizar proposta: ' . $e->getMessage()
            ], 500);
        }
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

            $votos_sim = $proposta->votos->where('voto', 1)->count();
            $votos_nao = $proposta->votos->where('voto', 0)->count();

            return response()->json([
                'proposta' => $proposta,
                'resultados' => [
                    'sim' => $votos_sim,
                    'nao' => $votos_nao,
                ],
                'total_votos' => $proposta->votos->count(),
                'votos' => $proposta->votos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'mensagem' => 'Proposta não encontrada.',
            ], 404);
        }
    }
}
