<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <title>Fattura {{ $fattura->displayNumber }}</title>
    <style>
        @page { margin: 24mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #222; font-size: 11pt; }
        h1 { font-size: 18pt; margin: 0 0 4pt; }
        .muted { color: #666; }
        .right { text-align: right; }
        .meta { width: 100%; margin: 16pt 0 24pt; }
        .meta td { padding: 2pt 0; vertical-align: top; }
        .meta .label { color: #666; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.5pt; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 12pt; }
        table.lines th, table.lines td { padding: 6pt 4pt; border-bottom: 1px solid #ddd; }
        table.lines th { background: #f5f5f5; text-align: left; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.5pt; }
        table.lines td.right, table.lines th.right { text-align: right; }
        .totals { margin-top: 16pt; width: 50%; margin-left: 50%; }
        .totals td { padding: 4pt 8pt; }
        .totals .total td { font-weight: bold; font-size: 13pt; border-top: 2px solid #222; padding-top: 8pt; }
        .footer { margin-top: 32pt; font-size: 9pt; color: #999; border-top: 1px solid #eee; padding-top: 8pt; }
    </style>
</head>
<body>
    <h1>Fattura {{ $fattura->displayNumber }}</h1>
    <div class="muted">{{ $companyName }}</div>

    <table class="meta">
        <tr>
            <td style="width:50%">
                <div class="label">{{ __('documents/labels.fields.client') }}</div>
                <div>
                    <strong>{{ $fattura->client?->name ?? '—' }}</strong><br>
                    @if ($fattura->client?->vatNumber)
                        P.IVA: {{ $fattura->client->vatNumber }}<br>
                    @endif
                    @if ($fattura->client?->taxCode)
                        C.F.: {{ $fattura->client->taxCode }}<br>
                    @endif
                    @if ($fattura->client?->address)
                        {{ $fattura->client->address['street'] ?? '' }}<br>
                        {{ $fattura->client->address['postal_code'] ?? '' }}
                        {{ $fattura->client->address['city'] ?? '' }}
                        {{ $fattura->client->address['province'] ?? '' }}
                    @endif
                </div>
            </td>
            <td style="width:50%" class="right">
                <div class="label">{{ __('documents/labels.fields.issued_at') }}</div>
                <div>{{ $fattura->issuedAt->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>{{ __('documents/labels.fields.line_description') }}</th>
                <th class="right">{{ __('documents/labels.fields.line_qty') }}</th>
                <th class="right">{{ __('documents/labels.fields.line_unit_price') }}</th>
                <th class="right">{{ __('documents/labels.fields.line_vat_rate') }}</th>
                <th class="right">{{ __('documents/labels.fields.subtotal') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($fattura->lines as $line)
            <tr>
                <td>{{ $line->description }}</td>
                <td class="right">{{ rtrim(rtrim(number_format($line->qty, 2, ',', '.'), '0'), ',') }}</td>
                <td class="right">{{ $line->unitPrice->format($locale) }}</td>
                <td class="right">{{ rtrim(rtrim(number_format($line->vatRate, 2, ',', '.'), '0'), ',') }}%</td>
                <td class="right">{{ $line->lineSubtotal()->format($locale) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>{{ __('documents/labels.fields.subtotal') }}</td>
            <td class="right">{{ $fattura->subtotal->format($locale) }}</td>
        </tr>
        <tr>
            <td>{{ __('documents/labels.fields.vat') }}</td>
            <td class="right">{{ $fattura->vat->format($locale) }}</td>
        </tr>
        <tr class="total">
            <td>{{ __('documents/labels.fields.total') }}</td>
            <td class="right">{{ $fattura->total->format($locale) }}</td>
        </tr>
    </table>

    <div class="footer">
        Documento generato automaticamente — non sostituisce la fattura elettronica trasmessa al SdI.
    </div>
</body>
</html>
