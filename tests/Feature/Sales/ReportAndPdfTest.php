<?php

namespace Tests\Feature\Sales;

use App\Enums\StockMovementType;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\InvoicePdfService;
use App\Services\ReportService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSubscribedBusiness;
use Tests\TestCase;

class ReportAndPdfTest extends TestCase
{
    use CreatesSubscribedBusiness;
    use RefreshDatabase;

    public function test_financial_report_summary(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create([
            'business_id' => $owner->business_id,
            'cost_price' => 20,
            'selling_price' => 50,
        ]);
        app(InventoryService::class)->adjust($product, $warehouse, StockMovementType::AdjustmentIn, 10, $owner);

        app(SaleService::class)->createCompleted($owner->business, $owner, [
            'warehouse_id' => $warehouse->id,
            'sale_date' => now()->toDateString(),
            'payment_amount' => 50,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 50]],
        ]);

        Expense::factory()->create([
            'business_id' => $owner->business_id,
            'amount' => 15,
            'expense_date' => now()->toDateString(),
        ]);

        $summary = app(ReportService::class)->financialSummary(
            $owner->business_id,
            now()->startOfMonth(),
            now()->endOfMonth(),
        );

        $this->assertSame('100.00', $summary['sales_total']);
        $this->assertSame('40.00', $summary['cogs']);
        $this->assertSame('60.00', $summary['gross_profit']);
        $this->assertSame('15.00', $summary['expenses_total']);
        $this->assertSame('45.00', $summary['net_profit']);
        $this->assertCount(1, $summary['top_products']);
    }

    public function test_invoice_pdf_download_is_authorized_and_returns_pdf(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create(['business_id' => $owner->business_id]);
        app(InventoryService::class)->adjust($product, $warehouse, StockMovementType::AdjustmentIn, 3, $owner);

        $sale = app(SaleService::class)->createCompleted($owner->business, $owner, [
            'warehouse_id' => $warehouse->id,
            'sale_date' => now()->toDateString(),
            'payment_amount' => 10,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10]],
        ]);

        $this->actingAs($owner)
            ->get(route('business.invoices.pdf', $sale->invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $other = $this->subscribedOwner();
        $this->actingAs($other)
            ->get(route('business.invoices.pdf', $sale->invoice))
            ->assertNotFound();
    }

    public function test_reports_page_renders_for_subscribed_owner(): void
    {
        $owner = $this->subscribedOwner();

        $this->actingAs($owner)
            ->get(route('business.reports.index'))
            ->assertOk()
            ->assertSee('Financial reports');
    }
}
