<?php

namespace App\Services;

use App\Enums\PlanFeatureKey;
use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(
        private readonly PlanLimitService $planLimits,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Business $business, User $actor, array $data): Product
    {
        $this->planLimits->requireAccessibleSubscription($business);

        $currentCount = Product::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->count();

        $this->planLimits->assertWithinLimit($business, PlanFeatureKey::ProductsLimit, $currentCount);

        return DB::transaction(function () use ($business, $actor, $data) {
            $this->assertUniqueSku($business->id, $data['sku']);
            $this->assertUniqueBarcode($business->id, $data['barcode'] ?? null);

            $product = Product::withoutGlobalScopes()->create([
                ...$data,
                'business_id' => $business->id,
            ]);

            $this->auditLogger->log(
                action: 'product.created',
                auditable: $product,
                newValues: $product->only(['name', 'sku', 'selling_price']),
                user: $actor,
            );

            return $product;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, User $actor, array $data): Product
    {
        $this->planLimits->requireAccessibleSubscription($product->business);

        return DB::transaction(function () use ($product, $actor, $data) {
            if (isset($data['sku'])) {
                $this->assertUniqueSku($product->business_id, $data['sku'], $product->id);
            }
            if (array_key_exists('barcode', $data)) {
                $this->assertUniqueBarcode($product->business_id, $data['barcode'], $product->id);
            }

            $old = $product->only(['name', 'sku', 'selling_price', 'cost_price', 'is_active']);
            $product->update($data);

            $this->auditLogger->log(
                action: 'product.updated',
                auditable: $product,
                oldValues: $old,
                newValues: $product->only(array_keys($old)),
                user: $actor,
            );

            return $product->refresh();
        });
    }

    public function delete(Product $product, User $actor): void
    {
        $this->planLimits->requireAccessibleSubscription($product->business);

        DB::transaction(function () use ($product, $actor) {
            $product->delete();

            $this->auditLogger->log(
                action: 'product.deleted',
                auditable: $product,
                oldValues: ['id' => $product->id, 'sku' => $product->sku],
                user: $actor,
            );
        });
    }

    private function assertUniqueSku(int $businessId, string $sku, ?int $ignoreId = null): void
    {
        $exists = Product::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('sku', $sku)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'sku' => 'SKU must be unique within your business.',
            ]);
        }
    }

    private function assertUniqueBarcode(int $businessId, ?string $barcode, ?int $ignoreId = null): void
    {
        if ($barcode === null || $barcode === '') {
            return;
        }

        $exists = Product::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('barcode', $barcode)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'barcode' => 'Barcode must be unique within your business.',
            ]);
        }
    }
}
