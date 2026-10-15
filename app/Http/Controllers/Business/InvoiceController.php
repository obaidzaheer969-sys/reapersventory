<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class InvoiceController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Invoice::class);

        return view('business.invoices.index', [
            'invoices' => Invoice::query()
                ->with(['customer', 'sale'])
                ->latest('id')
                ->paginate(15),
        ]);
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);
        $invoice->load(['customer', 'sale.items.product', 'sale.payments', 'business']);

        return view('business.invoices.show', compact('invoice'));
    }

    public function pdf(Invoice $invoice, InvoicePdfService $pdfs): SymfonyResponse
    {
        $this->authorize('download', $invoice);

        return $pdfs->download($invoice);
    }

    public function receipt(Invoice $invoice, InvoicePdfService $pdfs): SymfonyResponse
    {
        $this->authorize('download', $invoice);

        return $pdfs->downloadReceipt($invoice);
    }

    /**
     * Compact 80mm thermal bill for small POS / roll printers (browser print).
     */
    public function thermal(Request $request, Invoice $invoice): View
    {
        $this->authorize('download', $invoice);

        $invoice->load([
            'business',
            'customer',
            'sale.items.product',
            'sale.payments',
            'sale.warehouse',
        ]);

        return view('print.thermal-receipt', [
            'invoice' => $invoice,
            'business' => $invoice->business,
            'sale' => $invoice->sale,
            'logoDataUri' => $invoice->business?->brandLogoDataUri(),
            'autoPrint' => $request->boolean('autoprint'),
        ]);
    }
}
