<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Relatório de Unidades Avaliadas</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #bea55a; color: white; font-weight: bold; }
        .table tr:nth-child(even) { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .header img { max-height: 50px; }
        .nota { padding: 4px 8px; border-radius: 4px; }
        .bg-gray-100 { background-color: #f3f4f6; color: #1f2937; }
        .bg-green-600 { background-color: #16a34a; color: white; }
        .bg-green-500 { background-color: #22c55e; color: white; }
        .bg-yellow-500 { background-color: #eab308; color: white; }
        .bg-orange-500 { background-color: #f97316; color: white; }
        .bg-red-500 { background-color: #ef4444; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/Logo-pcpb.png') }}" alt="Logo da Polícia Civil">
        <h1>Relatório de Unidades Avaliadas</h1>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Cidade</th>
                <th>Unidade Gestora</th>
                <th>Sub-Gestora</th>
                <th>Status Formulário</th>
                <th>Nota Geral</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($unidades as $unidade)
                <tr>
                    <td>{{ $unidade['nome'] }}</td>
                    <td>{{ $unidade['cidade'] }}</td>
                    <td>{{ $unidade['srpc'] }}</td>
                    <td>{{ $unidade['dspc'] }}</td>
                    <td>{{ $unidade['status_formatado'] }}</td>
                    <td>
                        <span class="nota {{ $getNotaClass($unidade['nota_geral']) }}">
                            {{ $formatNota($unidade['nota_geral']) }}
                        </span>
                    </td>
                </tr>
            @endforeach
            @if ($unidades->isEmpty())
                <tr>
                    <td colspan="6" style="text-align: center;">Nenhuma unidade encontrada, aprove o formulário e dê uma avaliação.</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>