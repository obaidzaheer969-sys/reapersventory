<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0a1628; }
        .brand { color: #f15a24; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8fafc; }
        .meta { margin-top: 14px; }
        .totals { width: 280px; margin-left: auto; margin-top: 16px; }
        .totals td { border: 0; padding: 4px 0; }
        .right { text-align: right; }
        .header-table td { border: 0; vertical-align: top; padding: 0; }
        .logo { max-height: 64px; max-width: 180px; }
        .muted { color: #64748b; font-size: 11px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width:65%">
                @if (! empty($logoDataUri))
                    <img src="{{ $logoDataUri }}" class="logo" alt="Logo"><br>
                @else
                    <h1><span style="color:#0a1628">Reapers</span><span class="brand">Ventory</span></h1>
                @endif
                <div style="margin-top:6px"><strong>{{ $business->brandDisplayName() }}</strong></div>
                @if ($business->address && ! str_contains((string) $business->address, '@'))
                    <div class="muted">{{ $business->address }}@if($business->city), {{ $business->city }}@endif</div>
                @endif
                @if ($business->phone && ! str_contains((string) $business->phone, '@'))
                    <div class="muted">{{ $business->phone }}</div>
                @endif
            </td>
            <td style="width:35%" class="right">
                <h1 style="margin-bottom:8px">Invoice</h1>
                <div><strong>{{ $invoice->invoice_number }}</strong></div>
                <div class="muted">{{ $invoice->status->label() }}</div>
            </td>
        </tr>
    </table>

    <div class="meta">
        <table class="header-table">
            <tr>
                <td style="width:50%">
                    <strong>Bill to</strong><br>
                    {{ $sale->customerLabel() }}<br>
                    @if ($invoice->customer?->phone && ! str_contains((string) $invoice->customer->phone, '@'))
                        <span class="muted">{{ $invoice->customer->phone }}</span><br>
                    @endif
                    @if ($invoice->customer?->address)
                        <span class="muted">{{ $invoice->customer->address }}</span>
                    @endif
                </td>
                <td style="width:50%">
                    @php
                        $invoiceTime = ($invoice->created_at ?? $invoice->issued_at)
                            ->timezone($business->timezone ?: 'Asia/Karachi')
                            ->format('d M Y h:i A');
                    @endphp
                    <strong>Sale:</strong> {{ $sale->sale_number }}<br>
                    <strong>Invoice time:</strong> {{ $invoiceTime }} PKT<br>
                    <strong>Warehouse:</strong> {{ $sale->warehouse?->name ?? '—' }}
                </td>
            </tr>
        </table>
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
                    <td>{{ number_format($item->quantity, 3) }} {{ $item->product->unit }}</td>
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
