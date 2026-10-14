<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoicePdfService
{
    public function download(Invoice $invoice): Response
    {
        $invoice->load([
            'business',
            'customer',
            'sale.items.product',
            'sale.payments',
            'sale.warehouse',
        ]);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'business' => $invoice->business,
            'sale' => $invoice->sale,
        ])->setPaper('a4');

        return $pdf->download($invoice->invoice_number.'.pdf');
    }

    public function downloadReceipt(Invoice $invoice): Response
    {
        $invoice->load([
            'business',
            'customer',
            'sale.items.product',
            'sale.payments',
        ]);

        $pdf = Pdf::loadView('pdf.receipt', [
            'invoice' => $invoice,
            'business' => $invoice->business,
            'sale' => $invoice->sale,
        ])->setPaper('a4');

        return $pdf->download($invoice->invoice_number.'-receipt.pdf');
    }
}
