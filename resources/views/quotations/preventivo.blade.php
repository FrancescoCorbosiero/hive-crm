<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <title>Preventivo {{ $q->displayNumber() }}</title>
    <style>
        @page { margin: 24mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #222; font-size: 11pt; }
        h1 { font-size: 18pt; margin: 0 0 4pt; }
        .muted { color: #666; }
        .right { text-align: right; }
        .meta { width: 100%; margin: 16pt 0 24pt; }
        .meta td { padding: 2pt 0; vertical-align: top; }
        .label { color: #666; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.5pt; }
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
    <h1>Preventivo {{ $q->displayNumber() }}</h1>
    <div class="muted">{{ $companyName }} — {{ $q->name }}</div>

    <table class="meta">
        <tr>
            <td style="width:50%">
                <div class="label">Cliente</div>
                <div>
                    <strong>{{ $client?->name ?? '—' }}</strong><br>
                    @if ($client?->vatNumber)P.IVA: {{ $client->vatNumber }}<br>@endif
                    @if ($client?->address){{ $client->address['street'] ?? '' }}, {{ $client->address['city'] ?? '' }}@endif
                </div>
            </td>
            <td style="width:50%" class="right">
                <div class="label">Data</div>
                <div>{{ $q->issued_at->format('d/m/Y') }}</div>
                @if ($q->valid_until)
                    <div class="label" style="margin-top:8pt">Valido fino a</div>
                    <div>{{ $q->valid_until->format('d/m/Y') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>Descrizione</th>
                <th>Cadenza</th>
                <th class="right">Qtà</th>
                <th class="right">Prezzo</th>
                <th class="right">IVA %</th>
                <th class="right">Imponibile</th>
            </tr>
        </thead>
        <tbody>
        @foreach ((array) $q->lines as $line)
            @php
                $qty = (float)($line['qty'] ?? 0);
                $unit = (int)($line['unit_price_cents'] ?? 0);
                $rate = (float)($line['vat_rate'] ?? 0);
                $sub = (int) round($qty * $unit);
                $cadence = \App\Domains\Quotations\Enums\LineCadence::tryFrom(
                    $line['cadence'] ?? \App\Domains\Quotations\Enums\LineCadence::UnaTantum->value
                ) ?? \App\Domains\Quotations\Enums\LineCadence::UnaTantum;
            @endphp
            <tr>
                <td>{{ $line['description'] ?? '' }}</td>
                <td>{{ $cadence->label() }}</td>
                <td class="right">{{ rtrim(rtrim(number_format($qty, 2, ',', '.'), '0'), ',') }}</td>
                <td class="right">{{ (new \App\Shared\ValueObjects\Money($unit, $q->currency))->format($locale) }}</td>
                <td class="right">{{ rtrim(rtrim(number_format($rate, 2, ',', '.'), '0'), ',') }}%</td>
                <td class="right">{{ (new \App\Shared\ValueObjects\Money($sub, $q->currency))->format($locale) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Imponibile</td><td class="right">{{ $q->subtotal()->format($locale) }}</td></tr>
        <tr><td>IVA</td><td class="right">{{ $q->vat()->format($locale) }}</td></tr>
        <tr class="total"><td>Totale</td><td class="right">{{ $q->total()->format($locale) }}</td></tr>
    </table>

    <div class="footer">
        Preventivo non vincolante. La fattura sarà emessa al momento dell'accettazione e dell'inizio dei lavori.
    </div>
</body>
</html>
