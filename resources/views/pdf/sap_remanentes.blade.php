<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $tituloReporte }}</title>
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 8px;
        }
        .header h2 {
            margin: 0;
            color: #0056b3;
            font-size: 15pt;
            text-transform: uppercase;
        }
        .header h4 {
            margin: 4px 0 0;
            color: #444;
            font-size: 10pt;
        }
        .meta-info {
            width: 100%;
            margin-bottom: 12px;
            font-size: 8.5pt;
            color: #555;
        }
        .meta-info table {
            width: 100%;
            border: none;
        }
        .meta-info td {
            border: none;
            padding: 2px 0;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        table.data-table th {
            background-color: #0056b3;
            color: #fff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9pt;
            padding: 8px 6px;
            border: 1px solid #004085;
            text-align: center;
        }
        table.data-table td {
            padding: 6px 6px;
            border: 1px solid #ddd;
            font-size: 9pt;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .text-start { text-align: left; }
    </style>
</head>
<body>

    <div class="header">
        <h2>COLEGIO MÉDICO DE HONDURAS (CMH)</h2>
        <h4>{{ $tituloReporte }}</h4>
    </div>

    <div class="meta-info">
        <table>
            <tr>
                <td><strong>Generado por:</strong> {{ $usuario }}</td>
                <td style="text-align: right;"><strong>Fecha y Hora:</strong> {{ $fecha }}</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 18%;">Código Colegial</th>
                <th style="width: 22%;">No. Identificación</th>
                <th style="width: 40%;">Nombre</th>
                <th style="width: 20%;">Remanente</th>
            </tr>
        </thead>
        <tbody>
            @forelse($insumos as $item)
                <tr>
                    <td class="text-center">{{ $item['codigo_colegial'] ?? '' }}</td>
                    <td class="text-center">{{ $item['no_identificacion'] ?? '' }}</td>
                    <td class="text-start">{{ $item['nombre'] ?? '' }}</td>
                    <td class="text-end fw-bold">{{ number_format((float)($item['remanente'] ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No hay registros con remanentes.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>