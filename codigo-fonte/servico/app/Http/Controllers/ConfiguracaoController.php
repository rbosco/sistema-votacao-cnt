<?php

namespace App\Http\Controllers;

use App\Models\Configuracao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfiguracaoController extends Controller
{
    /**
     * Obter todas as configurações
     */
    public function index()
    {
        $configuracoes = Configuracao::obterTodas();

        // Adicionar informações extras sobre o temporizador
        $configuracoes['temporizador_ativo_verificado'] = Configuracao::temporizadorAtivo();
        $configuracoes['tempo_restante_segundos'] = Configuracao::tempoRestante();

        return response()->json($configuracoes);
    }

    /**
     * Atualizar configurações
     */
    public function atualizar(Request $request)
    {
        try {
            $request->validate([
                'temporizador_ativo' => 'sometimes|in:0,1',
                'temporizador_duracao_minutos' => 'sometimes|integer|min:1|max:1440',
            ], [
                'temporizador_ativo.in' => 'O temporizador deve ser 0 (inativo) ou 1 (ativo).',
                'temporizador_duracao_minutos.integer' => 'A duração deve ser um número inteiro.',
                'temporizador_duracao_minutos.min' => 'A duração mínima é 1 minuto.',
                'temporizador_duracao_minutos.max' => 'A duração máxima é 1440 minutos (24 horas).',
            ]);

            // Se está ativando o temporizador, definir o início
            if ($request->has('temporizador_ativo') && $request->temporizador_ativo === '1') {
                // Verificar se já está ativo e ainda não expirou
                $jaAtivo = Configuracao::obter('temporizador_ativo', '0') === '1';
                $tempoRestante = Configuracao::tempoRestante();

                // Se não está ativo ou já expirou, definir novo início
                if (!$jaAtivo || $tempoRestante <= 0) {
                    Configuracao::definir('temporizador_inicio', now()->toDateTimeString());
                }

                Configuracao::definir('temporizador_ativo', '1');
            } elseif ($request->has('temporizador_ativo') && $request->temporizador_ativo === '0') {
                // Desativando o temporizador
                Configuracao::definir('temporizador_ativo', '0');
            }

            // Atualizar duração se fornecida
            if ($request->has('temporizador_duracao_minutos')) {
                Configuracao::definir('temporizador_duracao_minutos', $request->temporizador_duracao_minutos);
            }

            return response()->json([
                'mensagem' => 'Configurações atualizadas com sucesso.',
                'configuracoes' => Configuracao::obterTodas(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao atualizar configurações', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'mensagem' => 'Erro ao atualizar configurações: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload de banner
     */
    public function uploadBanner(Request $request)
    {
        try {
            $request->validate([
                'banner' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            ], [
                'banner.required' => 'O banner é obrigatório.',
                'banner.image' => 'O arquivo deve ser uma imagem.',
                'banner.mimes' => 'O banner deve ser um arquivo do tipo: jpeg, png, jpg, gif.',
                'banner.max' => 'O banner não pode ter mais de 5MB.',
            ]);

            // Salvar o arquivo
            $arquivo = $request->file('banner');
            $nomeArquivo = 'banner-cnt-' . time() . '.' . $arquivo->getClientOriginalExtension();

            // Mover o arquivo para public/images
            $caminhoDestino = public_path('images');
            if (!file_exists($caminhoDestino)) {
                mkdir($caminhoDestino, 0755, true);
            }

            $arquivo->move($caminhoDestino, $nomeArquivo);

            // Atualizar configuração
            $caminhoRelativo = '/images/' . $nomeArquivo;
            Configuracao::definir('banner_votacao', $caminhoRelativo);

            return response()->json([
                'mensagem' => 'Banner atualizado com sucesso.',
                'caminho' => $caminhoRelativo,
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao fazer upload do banner', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'mensagem' => 'Erro ao fazer upload do banner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reiniciar temporizador
     */
    public function reiniciarTemporizador(Request $request)
    {
        try {
            // Definir novo início
            Configuracao::definir('temporizador_inicio', now()->toDateTimeString());
            Configuracao::definir('temporizador_ativo', '1');

            return response()->json([
                'mensagem' => 'Temporizador reiniciado com sucesso.',
                'inicio' => now()->toDateTimeString(),
                'tempo_restante_segundos' => Configuracao::tempoRestante(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao reiniciar temporizador', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'mensagem' => 'Erro ao reiniciar temporizador: ' . $e->getMessage()
            ], 500);
        }
    }
}
