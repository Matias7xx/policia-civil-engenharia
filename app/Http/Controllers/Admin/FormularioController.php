<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FormularioController extends Controller
{
    public function gerarRelatorio(Request $request)
    {
        // Verificar se o usuário é SuperAdmin
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Ação não autorizada.');
        }

        // Subconsulta para identificar os IDs das avaliações mais recentes para cada unidade
        $ultimasAvaliacoesQuery = DB::table('avaliacoes_unidade as a1')
            ->select('a1.id')
            ->whereRaw('a1.id = (SELECT MAX(a2.id) FROM avaliacoes_unidade as a2 WHERE a2.unidade_id = a1.unidade_id)');

        // Consulta para unidades aprovadas com avaliação
        $unidades = Unidade::select('unidades.*', 'avaliacoes_unidade.nota_geral')
            ->leftJoin('avaliacoes_unidade', function ($join) use ($ultimasAvaliacoesQuery) {
                $join->on('unidades.id', '=', 'avaliacoes_unidade.unidade_id')
                     ->whereIn('avaliacoes_unidade.id', $ultimasAvaliacoesQuery);
            })
            ->where('unidades.is_draft', false)
            ->where('unidades.status', 'aprovada')
            ->whereNotNull('avaliacoes_unidade.nota_geral')
            ->with(['team:id,name'])
            ->orderBy('unidades.nome')
            ->get()
            ->map(function ($unidade) {
                return [
                    'id' => $unidade->id,
                    'nome' => $unidade->nome,
                    'cidade' => $unidade->cidade ?? 'N/A',
                    'srpc' => $unidade->srpc ?? 'N/A',
                    'dspc' => $unidade->dspc ?? 'N/A',
                    'status_formatado' => $unidade->status_formatado ?? 'Aprovado',
                    'nota_geral' => $unidade->nota_geral,
                ];
            });

        // Determinar o formato do relatório
        $format = $request->query('format', 'pdf');

        if ($format === 'excel') {
            return Excel::download(new class($unidades) implements FromCollection, WithHeadings, WithStyles {
                protected $unidades;

                public function __construct($unidades)
                {
                    $this->unidades = $unidades;
                }

                public function collection()
                {
                    return $this->unidades->map(function ($unidade) {
                        return [
                            'Nome' => $unidade['nome'],
                            'Cidade' => $unidade['cidade'],
                            'Unidade Gestora' => $unidade['srpc'],
                            'Sub-Gestora' => $unidade['dspc'],
                            'Status Formulário' => $unidade['status_formatado'],
                            'Nota Geral' => $this->formatNota($unidade['nota_geral']),
                        ];
                    });
                }

                public function headings(): array
                {
                    return [
                        'Nome',
                        'Cidade',
                        'Unidade Gestora',
                        'Sub-Gestora',
                        'Status Formulário',
                        'Nota Geral',
                    ];
                }

                public function styles(Worksheet $sheet)
                {
                    // cabeçalho
                    $sheet->getStyle('A1:F1')->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFbea55a']],
                    ]);

                    // Aplicar cores às notas
                    foreach ($this->unidades as $index => $unidade) {
                        $nota = $unidade['nota_geral'];
                        $row = $index + 2; // Começa na linha 2 (após o cabeçalho)
                        $notaCell = "F{$row}";
                        $color = $this->getNotaColor($nota);
                        $sheet->getStyle($notaCell)->applyFromArray([
                            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $color]],
                            'font' => ['color' => ['argb' => 'FFFFFFFF']],
                        ]);
                    }

                    // Ajustar largura das colunas
                    foreach (range('A', 'F') as $col) {
                        $sheet->getColumnDimension($col)->setAutoSize(true);
                    }

                    return [];
                }

                private function formatNota($nota)
                {
                    if (!$nota) return 'N/A';
                    $notaNum = floatval($nota);
                    if (is_nan($notaNum)) return 'N/A';
                    $letra = $this->getNotaLetra($notaNum);
                    return number_format($notaNum, 1, ',', '.') . " ({$letra})";
                }

                private function getNotaLetra($nota)
                {
                    if ($nota >= 9.5) return 'A+';
                    if ($nota >= 9.0) return 'A';
                    if ($nota >= 8.0) return 'B';
                    if ($nota >= 7.0) return 'C';
                    if ($nota >= 6.0) return 'D';
                    if ($nota >= 5.0) return 'E';
                    if ($nota >= 4.0) return 'F';
                    if ($nota >= 3.0) return 'G';
                    if ($nota >= 2.0) return 'H';
                    if ($nota >= 1.0) return 'I';
                    return 'J';
                }

                private function getNotaColor($nota)
                {
                    if (!$nota) return 'FFd3d3d3'; // Cinza
                    $notaNum = floatval($nota);
                    if ($notaNum >= 9.0) return 'FF16a34a'; // Verde escuro
                    if ($notaNum >= 7.0) return 'FF22c55e'; // Verde claro
                    if ($notaNum >= 5.0) return 'FFeab308'; // Amarelo
                    if ($notaNum >= 3.0) return 'FFf97316'; // Laranja
                    return 'FFef4444'; // Vermelho
                }
            }, 'Relatorio_Unidades.xlsx');
        }

        // Geração de PDF
        $pdf = Pdf::loadView('pdf.unidades_relatorio', [
            'unidades' => $unidades,
            'getNotaClass' => function ($nota) {
                if (!$nota) return 'bg-gray-100 text-gray-800';
                $notaNum = floatval($nota);
                if ($notaNum >= 9.0) return 'bg-green-600 text-white';
                if ($notaNum >= 7.0) return 'bg-green-500 text-white';
                if ($notaNum >= 5.0) return 'bg-yellow-500 text-white';
                if ($notaNum >= 3.0) return 'bg-orange-500 text-white';
                return 'bg-red-500 text-white';
            },
            'formatNota' => function ($nota) {
                if (!$nota) return 'N/A';
                $notaNum = floatval($nota);
                if (is_nan($notaNum)) return 'N/A';
                $letra = $this->getNotaLetra($notaNum);
                return number_format($notaNum, 1, ',', '.') . " ({$letra})";
            },
            'getNotaLetra' => function ($nota) {
                if (!$nota) return '';
                $notaNum = floatval($nota);
                if ($notaNum >= 9.5) return 'A+';
                if ($notaNum >= 9.0) return 'A';
                if ($notaNum >= 8.0) return 'B';
                if ($notaNum >= 7.0) return 'C';
                if ($notaNum >= 6.0) return 'D';
                if ($notaNum >= 5.0) return 'E';
                if ($notaNum >= 4.0) return 'F';
                if ($notaNum >= 3.0) return 'G';
                if ($notaNum >= 2.0) return 'H';
                if ($notaNum >= 1.0) return 'I';
                return 'J';
            },
        ]);

        return $pdf->download('Relatorio_Unidades.pdf');
    }

    private function getNotaLetra($nota)
    {
        if (!$nota) return '';
        $notaNum = floatval($nota);
        if ($notaNum >= 9.5) return 'A+';
        if ($notaNum >= 9.0) return 'A';
        if ($notaNum >= 8.0) return 'B';
        if ($notaNum >= 7.0) return 'C';
        if ($notaNum >= 6.0) return 'D';
        if ($notaNum >= 5.0) return 'E';
        if ($notaNum >= 4.0) return 'F';
        if ($notaNum >= 3.0) return 'G';
        if ($notaNum >= 2.0) return 'H';
        if ($notaNum >= 1.0) return 'I';
        return 'J';
    }
}