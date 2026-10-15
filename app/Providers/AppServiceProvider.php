<?php

namespace App\Providers;

use App\Models\Business;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Policies\BusinessPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\ProductPolicy;
use App\Policies\ProductReturnPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\SalePolicy;
use App\Policies\SubscriptionPaymentPolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\WarehousePolicy;
use App\Services\InventoryService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        Gate::policy(Business::class, BusinessPolicy::class);
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
        Gate::policy(SubscriptionPayment::class, SubscriptionPaymentPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Warehouse::class, WarehousePolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(Purchase::class, PurchasePolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Expense::class, ExpensePolicy::class);
        Gate::policy(ProductReturn::class, ProductReturnPolicy::class);

        Gate::define('access-super-admin', fn ($user) => $user->isSuperAdmin());

        View::composer('layouts.partials.nav', function ($view): void {
            $unread = 0;
            $lowStock = 0;
            $user = Auth::user();

            // Hosting may not have run notifications migration yet — never 500 the whole app.
            if ($user && method_exists($user, 'unreadNotifications') && Schema::hasTable('notifications')) {
                try {
                    $unread = $user->unreadNotifications()->count();
                } catch (Throwable) {
                    $unread = 0;
                }
            }

            if ($user && $user->isBusinessOwner() && $user->business_id) {
                try {
                    $businessId = (int) $user->business_id;
                    $lowStock = (int) Cache::remember(
                        "nav-low-stock-count:{$businessId}",
                        now()->addSeconds(45),
                        fn () => count(app(InventoryService::class)->lowStockProducts($businessId)),
                    );
                } catch (Throwable) {
                    $lowStock = 0;
                }
            }

            // Prefer the louder signal so menu Alerts matches dashboard low-stock badge.
            $count = max($unread, $lowStock);

            $view->with('unreadNotificationsCount', $count);
            $view->with('navLowStockCount', $lowStock);
        });
    }
}
