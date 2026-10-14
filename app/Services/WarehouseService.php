<?php

namespace App\Services;

use App\Enums\PlanFeatureKey;
use App\Models\Business;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseService
{
    public function __construct(
        private readonly PlanLimitService $planLimits,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Business $business, User $actor, array $data): Warehouse
    {
        $this->planLimits->requireAccessibleSubscription($business);

        $currentCount = Warehouse::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->count();

        $this->planLimits->assertWithinLimit($business, PlanFeatureKey::WarehousesLimit, $currentCount);

        return DB::transaction(function () use ($business, $actor, $data) {
            $this->assertUniqueCode($business->id, $data['code']);

            if (! empty($data['is_default'])) {
                Warehouse::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->update(['is_default' => false]);
            } elseif ($currentCount === 0) {
                $data['is_default'] = true;
            }

            $warehouse = Warehouse::withoutGlobalScopes()->create([
                ...$data,
                'business_id' => $business->id,
            ]);

            $this->auditLogger->log(
                action: 'warehouse.created',
                auditable: $warehouse,
                newValues: $warehouse->only(['name', 'code', 'is_default']),
                user: $actor,
            );

            return $warehouse;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Warehouse $warehouse, User $actor, array $data): Warehouse
    {
        $this->planLimits->requireAccessibleSubscription($warehouse->business);

        return DB::transaction(function () use ($warehouse, $actor, $data) {
            if (isset($data['code'])) {
                $this->assertUniqueCode($warehouse->business_id, $data['code'], $warehouse->id);
            }

            if (! empty($data['is_default'])) {
                Warehouse::withoutGlobalScopes()
                    ->where('business_id', $warehouse->business_id)
                    ->where('id', '!=', $warehouse->id)
                    ->update(['is_default' => false]);
            }

            $warehouse->update($data);

            $this->auditLogger->log(
                action: 'warehouse.updated',
                auditable: $warehouse,
                newValues: $warehouse->only(['name', 'code', 'is_default', 'is_active']),
                user: $actor,
            );

            return $warehouse->refresh();
        });
    }

    private function assertUniqueCode(int $businessId, string $code, ?int $ignoreId = null): void
    {
        $exists = Warehouse::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('code', $code)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'code' => 'Warehouse code must be unique within your business.',
            ]);
        }
    }
}
