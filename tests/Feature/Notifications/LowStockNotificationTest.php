<?php

namespace Tests\Feature\Notifications;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Warehouse;
use App\Notifications\LowStockAlertNotification;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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
        ]);

        app(InventoryService::class)->adjust(
            $product,
            $warehouse,
            StockMovementType::AdjustmentIn,
            3,
            $owner,
        );

        Artisan::call('inventory:notify-low-stock');
        Artisan::call('inventory:notify-low-stock');

        Notification::assertSentToTimes($owner, LowStockAlertNotification::class, 1);
    }
}
