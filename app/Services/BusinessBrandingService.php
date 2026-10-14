<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BusinessBrandingService
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function update(Business $business, User $actor, array $data, ?UploadedFile $logo = null, bool $removeLogo = false): Business
    {
        if (! $this->subscriptions->isPaid($business)) {
            throw ValidationException::withMessages([
                'brand_name' => 'Custom branding is available only on a paid (active) subscription.',
            ]);
        }

        return DB::transaction(function () use ($business, $actor, $data, $logo, $removeLogo) {
            $old = $business->only(['brand_name', 'logo_path']);
            $payload = [
                'brand_name' => filled($data['brand_name'] ?? null)
                    ? trim((string) $data['brand_name'])
                    : null,
            ];

            if ($removeLogo && $business->logo_path) {
                Storage::disk('public')->delete($business->logo_path);
                $payload['logo_path'] = null;
            }

            if ($logo) {
                if ($business->logo_path) {
                    Storage::disk('public')->delete($business->logo_path);
                }

                $payload['logo_path'] = $logo->store('business-logos/'.$business->id, 'public');
            }

            $business->fill($payload)->save();

            $this->auditLogger->log(
                action: 'business.branding_updated',
                auditable: $business,
                oldValues: $old,
                newValues: $business->only(['brand_name', 'logo_path']),
                user: $actor,
            );

            return $business->refresh();
        });
    }
}
