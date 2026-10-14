<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0a1628; }
        .brand { color: #f15a24; }
        h1 { font-size: 22px; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8fafc; }
        .meta { margin-top: 12px; }
        .totals { width: 280px; margin-left: auto; margin-top: 16px; }
        .totals td { border: 0; padding: 4px 0; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1><span style="color:#0a1628">Reapers</span><span class="brand">Ventory</span></h1>
    <div>{{ $business->name }}</div>
    <div>{{ $business->email }} @if($business->phone) · {{ $business->phone }} @endif</div>

    <div class="meta">
        <strong>Invoice:</strong> {{ $invoice->invoice_number }}<br>
        <strong>Sale:</strong> {{ $sale->sale_number }}<br>
        <strong>Date:</strong> {{ $invoice->issued_at->toFormattedDateString() }}<br>
        <strong>Customer:</strong> {{ $invoice->customer?->name ?? 'Walk-in' }}<br>
        <strong>Status:</strong> {{ $invoice->status->label() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit price</th>
                <th class="right">Line total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->product->name }} ({{ $item->product->sku }})</td>
                    <td>{{ number_format($item->quantity, 3) }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td class="right">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="right">{{ number_format($invoice->subtotal, 2) }}</td></tr>
        <tr><td>Tax</td><td class="right">{{ number_format($invoice->tax, 2) }}</td></tr>
        <tr><td>Discount</td><td class="right">{{ number_format($invoice->discount, 2) }}</td></tr>
        <tr><td><strong>Total</strong></td><td class="right"><strong>{{ number_format($invoice->total, 2) }}</strong></td></tr>
        <tr><td>Paid</td><td class="right">{{ number_format($invoice->amount_paid, 2) }}</td></tr>
        <tr><td>Due</td><td class="right">{{ number_format($invoice->amount_due, 2) }}</td></tr>
    </table>
</body>
</html>
