<?php

namespace App\Http\Controllers;

use App\Models\Proposta;
use App\Models\Voto;
use App\Models\Configuracao;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class VotoController extends Controller
{
    /**
     * Registrar um voto
     */
    public function votar(Request $request)
    {
        $request->validate([
            'cpf_votante' => 'required|string|size:11',
            'bancada_id' => 'required|exists:bancadas,id',
            'voto' => 'required|in:0,1',
            'proposta_id' => 'required|exists:propostas,id',
        ], [
            'cpf_votante.required' => 'O CPF é obrigatório.',
            'cpf_votante.size' => 'O CPF deve ter 11 dígitos.',
            'bancada_id.required' => 'A bancada é obrigatória.',
            'bancada_id.exists' => 'A bancada selecionada não existe.',
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

        // Verificar o temporizador se estiver ativo
        $temporizadorAtivo = Configuracao::obter('temporizador_ativo', '0');
        if ($temporizadorAtivo === '1') {
            if (!Configuracao::temporizadorAtivo()) {
                return response()->json([
                    'message' => 'Tempo de votação encerrado.',
                ], 422);
            }
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
            'bancada_id' => $request->bancada_id,
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
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $query = Voto::with(['proposta', 'bancada'])
            ->orderBy('votado_em', 'desc');

        // Filtrar por proposta se fornecido
        if ($request->has('proposta_id') && $request->proposta_id) {
            $query->where('proposta_id', $request->proposta_id);
        }

        $votos = $query->paginate($perPage);

        // Adicionar ID criptografado da proposta
        $votos->getCollection()->transform(function ($voto) {
            if ($voto->proposta_id) {
                $voto->proposta_id_criptografado = encrypt($voto->proposta_id);
            }
            return $voto;
        });

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

    /**
     * Exportar votos para Excel
     */
    public function exportarExcel(Request $request)
    {
        $query = Voto::with(['proposta', 'bancada'])
            ->orderBy('votado_em', 'desc');

        // Filtrar por proposta se fornecido
        if ($request->has('proposta_id') && $request->proposta_id) {
            $query->where('proposta_id', $request->proposta_id);
        }

        $votos = $query->get();

        // Criar nova planilha
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar cabeçalhos
        $sheet->setCellValue('A1', 'Data/Hora');
        $sheet->setCellValue('B1', 'Proposta');
        $sheet->setCellValue('C1', 'CPF');
        $sheet->setCellValue('D1', 'Bancada');
        $sheet->setCellValue('E1', 'Voto');

        // Estilizar cabeçalhos
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1351b4']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ];
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

        // Ajustar largura das colunas
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(10);

        // Adicionar dados
        $row = 2;
        foreach ($votos as $voto) {
            $dataHora = $voto->votado_em ? date('d/m/Y H:i:s', strtotime($voto->votado_em)) : '-';
            $proposta = $voto->proposta ? "{$voto->proposta->numero} - {$voto->proposta->nome}" : '-';
            $cpf = $this->formatarCPF($voto->cpf_votante);
            $bancada = $voto->bancada ? $voto->bancada->nome : '-';
            $votoTexto = $voto->voto ? 'Sim' : 'Não';

            $sheet->setCellValue('A' . $row, $dataHora);
            $sheet->setCellValue('B' . $row, $proposta);
            $sheet->setCellValue('C' . $row, $cpf);
            $sheet->setCellValue('D' . $row, $bancada);
            $sheet->setCellValue('E' . $row, $votoTexto);

            $row++;
        }

        // Gerar arquivo
        $writer = new Xlsx($spreadsheet);
        $fileName = 'relatorio_votos_' . date('Y-m-d_H-i-s') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);

        $writer->save($tempFile);

        return response()->download($tempFile, $fileName, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Formatar CPF
     */
    private function formatarCPF($cpf)
    {
        if (strlen($cpf) !== 11) {
            return $cpf;
        }

        return substr($cpf, 0, 3) . '.' .
               substr($cpf, 3, 3) . '.' .
               substr($cpf, 6, 3) . '-' .
               substr($cpf, 9, 2);
    }
}
