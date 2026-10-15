<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
                $this->deleteLogoFile($business->logo_path);
                $payload['logo_path'] = null;
            }

            if ($logo) {
                if ($business->logo_path) {
                    $this->deleteLogoFile($business->logo_path);
                }

                $extension = strtolower((string) ($logo->getClientOriginalExtension() ?: $logo->extension() ?: 'png'));
                if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg'], true)) {
                    $extension = 'png';
                }

                $filename = Str::uuid()->toString().'.'.$extension;
                $directory = 'business-logos/'.$business->id;

                // Prefer public/uploads so logos work without storage:link on shared hosting.
                $stored = $logo->storeAs($directory, $filename, 'uploads');
                $payload['logo_path'] = 'uploads/'.$stored;
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

    private function deleteLogoFile(string $path): void
    {
        if (str_starts_with($path, 'uploads/')) {
            Storage::disk('uploads')->delete(substr($path, strlen('uploads/')));

            return;
        }

        Storage::disk('public')->delete($path);
    }
}
