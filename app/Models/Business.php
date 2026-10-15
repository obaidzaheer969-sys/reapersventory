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
        if (! filled($this->logo_path)) {
            return null;
        }

        $path = ltrim((string) $this->logo_path, '/');
        $version = optional($this->updated_at)?->timestamp;

        // Prefer direct public path checks (no disk config required on hosting).
        if (is_file(public_path($path))) {
            return '/'.$path.($version ? '?v='.$version : '');
        }

        if (str_starts_with($path, 'uploads/')) {
            $relative = substr($path, strlen('uploads/'));

            try {
                if (Storage::disk('uploads')->exists($relative)) {
                    return '/'.$path.($version ? '?v='.$version : '');
                }
            } catch (\Throwable) {
                // uploads disk may be missing from stale config cache
            }
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                return '/storage/'.$path.($version ? '?v='.$version : '');
            }
        } catch (\Throwable) {
            // ignore
        }

        return null;
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
