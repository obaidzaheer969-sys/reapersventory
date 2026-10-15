<?php

namespace Tests\Feature\Notifications;

use App\Enums\StockMovementType;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\Warehouse;
use App\Notifications\LowStockAlertNotification;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesSubscribedBusiness;
use Tests\TestCase;

class LowStockNotificationTest extends TestCase
{
    use CreatesSubscribedBusiness;
    use RefreshDatabase;

    public function test_low_stock_command_notifies_owner_once_per_day(): void
    {
        Notification::fake();

        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create([
            'business_id' => $owner->business_id,
            'reorder_level' => 10,
            'track_inventory' => true,
        ]);

        InventoryBalance::query()->create([
            'business_id' => $owner->business_id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'reserved_quantity' => 0,
        ]);

        Artisan::call('inventory:notify-low-stock');
        Artisan::call('inventory:notify-low-stock');

        Notification::assertSentToTimes($owner, LowStockAlertNotification::class, 1);
    }

    public function test_sale_triggers_immediate_low_stock_alert(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create([
            'business_id' => $owner->business_id,
            'reorder_level' => 5,
            'track_inventory' => true,
            'selling_price' => 10,
        ]);

        app(InventoryService::class)->adjust(
            $product,
            $warehouse,
            StockMovementType::AdjustmentIn,
            6,
            $owner,
        );

        Notification::fake();
        Cache::flush();

        app(InventoryService::class)->adjust(
            $product,
            $warehouse,
            StockMovementType::SaleOut,
            2,
            $owner,
        );

        // Stock now 4 <= reorder 5 → alert
        Notification::assertSentTo($owner, LowStockAlertNotification::class);
    }
}
