<?php

namespace App\Http\Controllers;

use App\Models\Proposta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PropostaController extends Controller
{
    /**
     * Listar todas as propostas
     */
    public function index(Request $request)
    {
        $query = Proposta::withCount([
            'votos',
            'votos as total_votos_sim' => function ($query) {
                $query->where('voto', 1);
            },
            'votos as total_votos_nao' => function ($query) {
                $query->where('voto', 0);
            }
        ]);

        // Filtro de pesquisa
        if ($request->has('busca') && !empty($request->busca)) {
            $busca = $request->busca;
            $query->where(function($q) use ($busca) {
                $q->where('numero', 'like', "%{$busca}%")
                  ->orWhere('nome', 'like', "%{$busca}%");
            });
        }

        $query->orderBy('created_at', 'desc');

        // Se não forneceu page, retorna todos; senão, retorna paginado
        if (!$request->has('page')) {
            $propostas = $query->get();

            // Adicionar ID criptografado
            $propostas->transform(function ($proposta) {
                $proposta->id_criptografado = encrypt($proposta->id);
                return $proposta;
            });
        } else {
            $perPage = $request->get('per_page', 10);
            $propostas = $query->paginate($perPage);

            // Adicionar ID criptografado
            $propostas->getCollection()->transform(function ($proposta) {
                $proposta->id_criptografado = encrypt($proposta->id);
                return $proposta;
            });
        }

        return response()->json($propostas);
    }

    /**
     * Retornar estatísticas do dashboard
     */
    public function dashboard()
    {
        $totalPropostas = Proposta::count();
        $propostasAtivas = Proposta::where('esta_ativa', true)->count();
        $totalVotos = \App\Models\Voto::count();
        $usuariosCadastrados = \App\Models\Usuario::count();

        return response()->json([
            'total_propostas' => $totalPropostas,
            'propostas_ativas' => $propostasAtivas,
            'total_votos' => $totalVotos,
            'usuarios_cadastrados' => $usuariosCadastrados,
        ]);
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
            'status' => 'nullable|in:nao_iniciada,em_votacao,encerrada',
        ], [
            'nome.required' => 'O nome da proposta é obrigatório.',
            'nome.max' => 'O nome não pode ter mais de :max caracteres.',
            'numero.max' => 'O número não pode ter mais de :max caracteres.',
            'status.in' => 'O status deve ser "nao_iniciada", "em_votacao" ou "encerrada".',
        ]);

        $proposta = Proposta::create([
            'numero' => $request->numero,
            'nome' => $request->nome,
            'esta_ativa' => false,
            'status' => $request->status ?? 'nao_iniciada',
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
                'status' => 'nullable|in:nao_iniciada,em_votacao,encerrada',
            ], [
                'nome.required' => 'O nome da proposta é obrigatório.',
                'nome.max' => 'O nome não pode ter mais de :max caracteres.',
                'numero.max' => 'O número não pode ter mais de :max caracteres.',
                'status.in' => 'O status deve ser "nao_iniciada", "em_votacao" ou "encerrada".',
            ]);

            $proposta = Proposta::findOrFail($id);
            \Log::info('Proposta encontrada', ['proposta' => $proposta->toArray()]);

            // Verificar se está tentando ativar uma proposta "em_votacao" quando já existe outra ativa "em_votacao"
            if ($request->has('esta_ativa') && $request->esta_ativa &&
                $request->status === 'em_votacao') {
                $outraEmVotacao = Proposta::where('id', '!=', $id)
                    ->where('esta_ativa', true)
                    ->where('status', 'em_votacao')
                    ->exists();

                if ($outraEmVotacao) {
                    return response()->json([
                        'message' => 'Não é possível ativar esta proposta com status "Em Votação". Já existe outra proposta ativa em votação. Altere o status para "Não Iniciada" ou "Encerrada".'
                    ], 422);
                }
            }

            // Verificar se está tentando alterar o status para "em_votacao" de uma proposta ativa quando já existe outra
            if ($request->has('status') && $request->status === 'em_votacao' && $proposta->esta_ativa) {
                $outraEmVotacao = Proposta::where('id', '!=', $id)
                    ->where('esta_ativa', true)
                    ->where('status', 'em_votacao')
                    ->exists();

                if ($outraEmVotacao) {
                    return response()->json([
                        'message' => 'Não é possível alterar o status para "Em Votação". Já existe outra proposta ativa em votação.'
                    ], 422);
                }
            }

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
                        'status' => $request->status ?? $proposta->status,
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
                    'status' => $request->status ?? $proposta->status,
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
     * Atualizar apenas o status de uma proposta
     */
    public function atualizarStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:nao_iniciada,em_votacao,encerrada',
            ], [
                'status.required' => 'O status é obrigatório.',
                'status.in' => 'O status deve ser "nao_iniciada", "em_votacao" ou "encerrada".',
            ]);

            $proposta = Proposta::findOrFail($id);

            // Verificar se está tentando alterar para "em_votacao" quando a proposta está ativa e já existe outra
            if ($request->status === 'em_votacao' && $proposta->esta_ativa) {
                $outraEmVotacao = Proposta::where('id', '!=', $id)
                    ->where('esta_ativa', true)
                    ->where('status', 'em_votacao')
                    ->exists();

                if ($outraEmVotacao) {
                    return response()->json([
                        'message' => 'Não é possível alterar o status para "Em Votação". Já existe outra proposta ativa em votação.'
                    ], 422);
                }
            }

            $proposta->update(['status' => $request->status]);

            return response()->json($proposta);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar status: ' . $e->getMessage()
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

    /**
     * Download do modelo Excel para importação de propostas
     */
    public function downloadModelo()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar cabeçalhos
        $sheet->setCellValue('A1', 'Número');
        $sheet->setCellValue('B1', 'Nome');
        $sheet->setCellValue('C1', 'Ativa (SIM/NÃO)');
        $sheet->setCellValue('D1', 'Status (nao_iniciada/em_votacao/encerrada)');

        // Estilizar cabeçalhos
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1351b4']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ];
        $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);

        // Ajustar largura das colunas
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(50);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(35);

        // Adicionar exemplos
        $sheet->setCellValue('A2', '1');
        $sheet->setCellValue('B2', 'Proposta de Exemplo 1');
        $sheet->setCellValue('C2', 'NÃO');
        $sheet->setCellValue('D2', 'nao_iniciada');

        $sheet->setCellValue('A3', '2');
        $sheet->setCellValue('B3', 'Proposta de Exemplo 2');
        $sheet->setCellValue('C3', 'NÃO');
        $sheet->setCellValue('D3', 'em_votacao');

        $sheet->setCellValue('A4', '3');
        $sheet->setCellValue('B4', 'Proposta de Exemplo 3');
        $sheet->setCellValue('C4', 'SIM');
        $sheet->setCellValue('D4', 'encerrada');

        // Gerar arquivo
        $writer = new Xlsx($spreadsheet);
        $fileName = 'modelo_importacao_propostas.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);

        $writer->save($tempFile);

        return response()->download($tempFile, $fileName, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Importar propostas de arquivo Excel
     */
    public function importarExcel(Request $request)
    {
        $request->validate([
            'arquivo' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            $arquivo = $request->file('arquivo');
            $spreadsheet = IOFactory::load($arquivo->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $importadas = 0;
            $erros = [];

            // Pular cabeçalho (linha 1)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                // Validar se a linha tem dados
                if (empty($row[0]) && empty($row[1])) {
                    continue;
                }

                $numero = trim($row[0] ?? '');
                $nome = trim($row[1] ?? '');
                $ativa = strtoupper(trim($row[2] ?? 'NÃO'));
                $status = strtolower(trim($row[3] ?? 'nao_iniciada'));

                // Validar nome obrigatório
                if (empty($nome)) {
                    $erros[] = "Linha " . ($i + 1) . ": Nome é obrigatório";
                    continue;
                }

                // Converter "SIM/NÃO" para boolean
                $estaAtiva = ($ativa === 'SIM');

                // Validar e normalizar status
                if (!in_array($status, ['nao_iniciada', 'em_votacao', 'encerrada'])) {
                    $status = 'nao_iniciada';
                }

                // Criar proposta
                Proposta::create([
                    'numero' => $numero,
                    'nome' => $nome,
                    'esta_ativa' => $estaAtiva,
                    'status' => $status
                ]);

                $importadas++;
            }

            return response()->json([
                'mensagem' => "Importação concluída! {$importadas} proposta(s) importada(s).",
                'importadas' => $importadas,
                'erros' => $erros
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'mensagem' => 'Erro ao importar arquivo: ' . $e->getMessage()
            ], 500);
        }
    }
}
