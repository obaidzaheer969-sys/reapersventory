<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\SaleStatus;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Carbon;

class ReportService
{
    /**
     * @return array{
     *   from: string,
     *   to: string,
     *   sales_total: string,
     *   sales_count: int,
     *   payments_collected: string,
     *   amount_due: string,
     *   purchases_total: string,
     *   expenses_total: string,
     *   gross_profit: string,
     *   net_profit: string,
     *   top_products: list<array{product_id: int, name: string, qty: string, revenue: string}>
     * }
     */
    public function financialSummary(int $businessId, Carbon $from, Carbon $to): array
    {
        $fromDate = $from->copy()->startOfDay();
        $toDate = $to->copy()->endOfDay();

        $sales = Sale::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->where('status', SaleStatus::Completed->value)
            ->where(function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('sale_date', [$fromDate->toDateString(), $toDate->toDateString()])
                    ->orWhereBetween('created_at', [$fromDate, $toDate]);
            })
            ->get(['id', 'total', 'amount_paid', 'amount_due']);

        $salesTotal = $sales->reduce(fn ($carry, $sale) => bcadd($carry, (string) $sale->total, 2), '0.00');
        $paymentsCollected = $sales->reduce(fn ($carry, $sale) => bcadd($carry, (string) $sale->amount_paid, 2), '0.00');
        $amountDue = $sales->reduce(fn ($carry, $sale) => bcadd($carry, (string) $sale->amount_due, 2), '0.00');

        $saleIds = $sales->pluck('id');

        $cogs = $saleIds->isEmpty()
            ? '0.00'
            : SaleItem::withoutGlobalScopes()
                ->whereIn('sale_id', $saleIds)
                ->get()
                ->reduce(
                    fn ($carry, $item) => bcadd(
                        $carry,
                        bcmul((string) $item->quantity, (string) $item->unit_cost, 2),
                        2
                    ),
                    '0.00'
                );

        $purchasesTotal = (string) Purchase::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('status', 'received')
            ->whereBetween('purchase_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->sum('total');

        $expensesTotal = (string) Expense::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereBetween('expense_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->sum('amount');

        $purchasesTotal = number_format((float) $purchasesTotal, 2, '.', '');
        $expensesTotal = number_format((float) $expensesTotal, 2, '.', '');

        $grossProfit = bcsub($salesTotal, $cogs, 2);
        $netProfit = bcsub($grossProfit, $expensesTotal, 2);

        $topProducts = $saleIds->isEmpty()
            ? []
            : SaleItem::withoutGlobalScopes()
                ->selectRaw('product_id, SUM(quantity) as qty, SUM(line_total) as revenue')
                ->whereIn('sale_id', $saleIds)
                ->groupBy('product_id')
                ->orderByDesc('revenue')
                ->limit(5)
                ->with('product:id,name')
                ->get()
                ->map(fn ($row) => [
                    'product_id' => (int) $row->product_id,
                    'name' => $row->product?->name ?? 'Unknown',
                    'qty' => number_format((float) $row->qty, 3, '.', ''),
                    'revenue' => number_format((float) $row->revenue, 2, '.', ''),
                ])
                ->all();

        $openInvoices = Invoice::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereIn('status', [InvoiceStatus::Unpaid->value, InvoiceStatus::Partial->value])
            ->where(function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('issued_at', [$fromDate->toDateString(), $toDate->toDateString()])
                    ->orWhereBetween('created_at', [$fromDate, $toDate]);
            })
            ->count();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'sales_total' => $salesTotal,
            'sales_count' => $sales->count(),
            'payments_collected' => $paymentsCollected,
            'amount_due' => $amountDue,
            'cogs' => $cogs,
            'purchases_total' => $purchasesTotal,
            'expenses_total' => $expensesTotal,
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
            'open_invoices' => $openInvoices,
            'top_products' => $topProducts,
        ];
    }
}
