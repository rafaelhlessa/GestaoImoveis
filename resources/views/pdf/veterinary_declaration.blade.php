<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Declaração Inspetoria Veterinária</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 12px; }
        .container { width: 100%; margin: 0 auto; }
        h1 { font-size: 18px; text-align: center; margin-bottom: 12px; }
        .meta { margin-bottom: 16px; }
        .meta div { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background-color: #f3f4f6; }
        .footer { margin-top: 24px; font-size: 11px; color: #6b7280; }
    </style>
    <!-- Documento sem cabeçalho conforme solicitado -->
    <!-- No header -->
    <!-- ✅ -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="pdf:generator" content="spatie/laravel-pdf">
    <meta name="app" content="Gestão de Imóveis">
    <meta name="generated-at" content="{{ now()->format('d/m/Y H:i') }}">
    <meta name="property-id" content="{{ $property->id }}">
    <meta name="locale" content="pt-BR">
    <meta name="charset" content="UTF-8">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' data: 'unsafe-inline' 'unsafe-eval';">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="format-detection" content="telephone=no">
</head>
<body>
    <div class="container">
        <h1>Declaração da Inspetoria Veterinária</h1>
        <div class="meta">
            <div><strong>Propriedade:</strong> {{ $property->nickname ?? ('Propriedade #' . $property->id) }}</div>
            <div><strong>Cidade:</strong> {{ $property->city ?? '-' }} | <strong>Distrito/Subdistrito:</strong> {{ $property->district ?? '-' }}</div>
            <div><strong>Localidade/Bairro:</strong> {{ $property->locality ?? '-' }}</div>
            <div><strong>Gerado em:</strong> {{ now()->format('d/m/Y H:i') }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Espécie</th>
                    <th>Faixa Etária</th>
                    <th>Qtd. Machos</th>
                    <th>Qtd. Fêmeas</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @php $grandTotal = 0; @endphp
                @forelse($entries as $row)
                    @php $total = (int)($row->qty_males ?? 0) + (int)($row->qty_females ?? 0); $grandTotal += $total; @endphp
                    <tr>
                        <td>{{ $row->species }}</td>
                        <td>{{ $row->age_band }}</td>
                        <td style="text-align:center;">{{ (int)($row->qty_males ?? 0) }}</td>
                        <td style="text-align:center;">{{ (int)($row->qty_females ?? 0) }}</td>
                        <td style="text-align:center; font-weight:bold;">{{ $total }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; color:#6b7280;">Nenhuma entrada registrada.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" style="text-align:right;">Total Geral</th>
                    <th style="text-align:center;">{{ $grandTotal }}</th>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            <p>Documento gerado automaticamente por sistema.</p>
        </div>
    </div>
</body>
</html>
