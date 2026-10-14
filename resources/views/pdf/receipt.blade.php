<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0a1628; }
        .brand { color: #f15a24; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border-bottom: 1px solid #ddd; padding: 6px; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1><span>Reapers</span><span class="brand">Ventory</span> Receipt</h1>
    <div>{{ $business->name }}</div>
    <p>
        Invoice {{ $invoice->invoice_number }} · Sale {{ $sale->sale_number }}<br>
        {{ $invoice->issued_at->toFormattedDateString() }} · {{ $invoice->customer?->name ?? 'Walk-in' }}
    </p>

    <table>
        <thead><tr><th>Item</th><th>Qty</th><th class="right">Amount</th></tr></thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ number_format($item->quantity, 3) }}</td>
                    <td class="right">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top:16px">
        <strong>Total:</strong> {{ number_format($invoice->total, 2) }}<br>
        <strong>Paid:</strong> {{ number_format($invoice->amount_paid, 2) }}<br>
        <strong>Due:</strong> {{ number_format($invoice->amount_due, 2) }}
    </p>

    @if ($sale->payments->isNotEmpty())
        <h3>Payments</h3>
        <table>
            <thead><tr><th>Date</th><th>Method</th><th class="right">Amount</th></tr></thead>
            <tbody>
                @foreach ($sale->payments as $payment)
                    <tr>
                        <td>{{ $payment->paid_at->toDayDateTimeString() }}</td>
                        <td>{{ strtoupper($payment->method) }}</td>
                        <td class="right">{{ number_format($payment->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
