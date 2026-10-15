<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bill {{ $invoice->invoice_number }}</title>
    <style>
        :root {
            --ticket-width: 80mm;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #e2e8f0;
            color: #0a1628;
            font-family: "Courier New", Courier, monospace;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            padding: 12px;
            background: #0a1628;
        }
        .toolbar a, .toolbar button {
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            font: 600 13px/1 Segoe UI, sans-serif;
            cursor: pointer;
            text-decoration: none;
            color: #fff;
        }
        .toolbar .print-btn { background: #f15a24; }
        .toolbar .secondary { background: #334155; }
        .stage {
            min-height: calc(100vh - 56px);
            display: flex;
            justify-content: center;
            padding: 20px 12px 40px;
        }
        .ticket {
            width: var(--ticket-width);
            max-width: 100%;
            background: #fff;
            padding: 10px 8px 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .muted { color: #475569; font-size: 11px; }
        .brand {
            font-family: Segoe UI, Helvetica Neue, Arial, sans-serif;
            font-size: 16px;
            font-weight: 800;
            line-height: 1.2;
            margin: 0 0 4px;
        }
        .logo {
            max-width: 48mm;
            max-height: 18mm;
            margin: 0 auto 6px;
            display: block;
        }
        .rule {
            border: 0;
            border-top: 1px dashed #94a3b8;
            margin: 8px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th {
            text-align: left;
            font-size: 11px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
        }
        td { padding: 4px 0; vertical-align: top; }
        .totals td { padding: 2px 0; }
        .totals .grand td {
            padding-top: 6px;
            font-weight: 800;
            font-size: 14px;
        }
        .footer-note {
            margin-top: 10px;
            font-size: 11px;
            text-align: center;
        }
        @media print {
            @page {
                size: 80mm auto;
                margin: 2mm;
            }
            body { background: #fff; }
            .toolbar { display: none !important; }
            .stage { padding: 0; min-height: 0; }
            .ticket {
                width: 76mm;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="print-btn" onclick="window.print()">Print bill</button>
        <a class="secondary" href="{{ route('business.sales.create') }}">New sale</a>
        <a class="secondary" href="{{ route('business.sales.show', $sale) }}">Sale details</a>
    </div>

    <div class="stage">
        <div class="ticket">
            <div class="center">
                @if (! empty($logoDataUri))
                    <img src="{{ $logoDataUri }}" class="logo" alt="Logo">
                @endif
                <h1 class="brand">{{ $business->brandDisplayName() }}</h1>
                @php
                    $billPhone = filled($business->phone) && ! str_contains((string) $business->phone, '@')
                        ? $business->phone
                        : null;
                    $billAddress = filled($business->address) && ! str_contains((string) $business->address, '@')
                        ? $business->address
                        : null;
                    $invoiceTime = ($invoice->created_at ?? $invoice->issued_at)
                        ->timezone($business->timezone ?: 'Asia/Karachi')
                        ->format('d M Y h:i A');
                @endphp
                @if ($billPhone)
                    <div class="muted">{{ $billPhone }}</div>
                @endif
                @if ($billAddress)
                    <div class="muted">{{ $billAddress }}@if($business->city), {{ $business->city }}@endif</div>
                @endif
            </div>

            <hr class="rule">

            <div class="muted">
                Invoice: {{ $invoice->invoice_number }}<br>
                Sale: {{ $sale->sale_number }}<br>
                Invoice time: {{ $invoiceTime }} PKT<br>
                Warehouse: {{ $sale->warehouse?->name ?? '—' }}<br>
                Customer: {{ $invoice->customer?->name ?? 'Walk-in' }}
                @if ($invoice->customer?->phone && ! str_contains((string) $invoice->customer->phone, '@'))
                    <br>Phone: {{ $invoice->customer->phone }}
                @endif
            </div>

            <hr class="rule">

            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="right">Qty</th>
                        <th class="right">Amt</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $item)
                        <tr>
                            <td>
                                {{ $item->product->name }}
                                <div class="muted">{{ number_format($item->unit_price, 2) }} / {{ $item->product->unit }}</div>
                            </td>
                            <td class="right">{{ rtrim(rtrim(number_format($item->quantity, 3, '.', ''), '0'), '.') }}</td>
                            <td class="right">{{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <hr class="rule">

            <table class="totals">
                <tr>
                    <td>Subtotal</td>
                    <td class="right">{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td>Tax</td>
                    <td class="right">{{ number_format($invoice->tax, 2) }}</td>
                </tr>
                <tr>
                    <td>Discount</td>
                    <td class="right">{{ number_format($invoice->discount, 2) }}</td>
                </tr>
                <tr class="grand">
                    <td>TOTAL</td>
                    <td class="right">{{ number_format($invoice->total, 2) }}</td>
                </tr>
                <tr>
                    <td>Paid</td>
                    <td class="right">{{ number_format($invoice->amount_paid, 2) }}</td>
                </tr>
                <tr>
                    <td>Due</td>
                    <td class="right">{{ number_format($invoice->amount_due, 2) }}</td>
                </tr>
            </table>

            @if ($sale->payments->isNotEmpty())
                <hr class="rule">
                <div class="muted">
                    @foreach ($sale->payments as $payment)
                        {{ strtoupper($payment->method) }}: {{ number_format($payment->amount, 2) }}<br>
                    @endforeach
                </div>
            @endif

            <div class="footer-note">
                Thank you!<br>
                <span class="muted">Powered by ReapersVentory</span>
            </div>
        </div>
    </div>

    @if ($autoPrint)
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () { window.print(); }, 250);
            });
        </script>
    @endif
</body>
</html>
