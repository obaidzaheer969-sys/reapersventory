<?php

namespace Tests\Feature\Sales;

use App\Enums\StockMovementType;
use App\Livewire\Business\SaleForm;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesSubscribedBusiness;
use Tests\TestCase;

class BarcodeSaleScanTest extends TestCase
{
    use CreatesSubscribedBusiness;
    use RefreshDatabase;

    public function test_barcode_scan_adds_product_line_on_sale_form(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->default()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create([
            'business_id' => $owner->business_id,
            'barcode' => '8901234567890',
            'sku' => 'N10-55645',
            'selling_price' => 200,
        ]);
        app(InventoryService::class)->adjust($product, $warehouse, StockMovementType::AdjustmentIn, 5, $owner);

        Livewire::actingAs($owner)
            ->test(SaleForm::class)
            ->set('barcodeScan', '8901234567890')
            ->call('scanBarcode')
            ->assertHasNoErrors()
            ->assertSet('items.0.product_id', $product->id)
            ->assertSet('items.0.quantity', '1')
            ->assertSet('items.0.unit_price', '200.00')
            ->assertSet('barcodeScan', '');
    }

    public function test_scanning_same_barcode_increments_quantity(): void
    {
        $owner = $this->subscribedOwner();
        Warehouse::factory()->default()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create([
            'business_id' => $owner->business_id,
            'barcode' => '1112223334445',
            'selling_price' => 50,
        ]);

        Livewire::actingAs($owner)
            ->test(SaleForm::class)
            ->set('barcodeScan', '1112223334445')
            ->call('scanBarcode')
            ->set('barcodeScan', '1112223334445')
            ->call('scanBarcode')
            ->assertSet('items.0.product_id', $product->id)
            ->assertSet('items.0.quantity', '2.000');
    }
}
