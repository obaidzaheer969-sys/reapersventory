<?php

namespace App\Models;

use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'slug',
    'email',
    'phone',
    'address',
    'city',
    'country',
    'timezone',
    'currency',
    'logo_path',
    'brand_name',
    'is_active',
])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Business $business): void {
            if (empty($business->slug)) {
                $business->slug = static::uniqueSlugFromName($business->name);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('role', \App\Enums\UserRole::BusinessOwner->value);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function brandDisplayName(): string
    {
        return filled($this->brand_name) ? (string) $this->brand_name : (string) $this->name;
    }

    public function brandLogoUrl(): ?string
    {
        $absolute = $this->brandLogoAbsolutePath();
        if (! $absolute) {
            return null;
        }

        $path = ltrim((string) $this->logo_path, '/');
        $version = optional($this->updated_at)?->timestamp;

        if (str_starts_with($path, 'uploads/') || is_file(public_path($path))) {
            return '/'.$path.($version ? '?v='.$version : '');
        }

        return '/storage/'.$path.($version ? '?v='.$version : '');
    }

    /**
     * Absolute filesystem path for DomPDF (remote URLs are unreliable).
     */
    public function brandLogoAbsolutePath(): ?string
    {
        if (! filled($this->logo_path)) {
            return null;
        }

        $path = ltrim((string) $this->logo_path, '/');

        if (is_file(public_path($path))) {
            return public_path($path);
        }

        if (str_starts_with($path, 'uploads/')) {
            $relative = substr($path, strlen('uploads/'));
            $candidate = public_path('uploads/'.$relative);
            if (is_file($candidate)) {
                return $candidate;
            }

            try {
                if (Storage::disk('uploads')->exists($relative)) {
                    return Storage::disk('uploads')->path($relative);
                }
            } catch (\Throwable) {
                // uploads disk may be missing from stale config cache
            }
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->path($path);
            }
        } catch (\Throwable) {
            // ignore
        }

        return null;
    }

    /**
     * Base64 data URI for embedding logos in PDFs.
     */
    public function brandLogoDataUri(): ?string
    {
        $absolute = $this->brandLogoAbsolutePath();
        if (! $absolute || ! is_file($absolute)) {
            return null;
        }

        $mime = mime_content_type($absolute) ?: 'image/png';
        $contents = file_get_contents($absolute);
        if ($contents === false) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }


    public static function uniqueSlugFromName(string $name): string
    {
        $base = Str::slug($name) ?: 'business';
        $slug = $base;
        $counter = 1;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
