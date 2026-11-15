<?php

use App\Http\Controllers\Admin\PaymentProofDownloadController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Business\CategoryController;
use App\Http\Controllers\Business\CustomerController;
use App\Http\Controllers\Business\DashboardController;
use App\Http\Controllers\Business\ExpenseController;
use App\Http\Controllers\Business\InventoryController;
use App\Http\Controllers\Business\InvoiceController;
use App\Http\Controllers\Business\LowStockController;
use App\Http\Controllers\Business\NotificationController;
use App\Http\Controllers\Business\ProductController;
use App\Http\Controllers\Business\ProfileController;
use App\Http\Controllers\Business\PurchaseController;
use App\Http\Controllers\Business\ReportController;
use App\Http\Controllers\Business\ReturnController;
use App\Http\Controllers\Business\SaleController;
use App\Http\Controllers\Business\ScanBarcodeController;
use App\Http\Controllers\Business\SubscriptionController;
use App\Http\Controllers\Business\SupplierController;
use App\Http\Controllers\Business\TeamUserController;
use App\Http\Controllers\Business\WarehouseController;
use App\Http\Controllers\Earning\AnnouncementController as EarningAnnouncementController;
use App\Http\Controllers\Earning\DashboardController as EarningDashboardController;
use App\Http\Controllers\Earning\EarningsController as EarningEarningsController;
use App\Http\Controllers\Earning\NotificationController as EarningNotificationController;
use App\Http\Controllers\Earning\PayoutController as EarningPayoutController;
use App\Http\Controllers\Earning\ProfileController as EarningProfileController;
use App\Http\Controllers\Earning\TaskController as EarningTaskController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PricingController;
use App\Models\PaymentProof;
use Illuminate\Support\Facades\Route;

Route::bind('paymentProof', fn (string $value) => PaymentProof::withoutGlobalScopes()->findOrFail($value));

Route::get('/', HomeController::class)->name('home');
Route::get('/pricing', PricingController::class)->name('pricing');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:register');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', EarningDashboardController::class)->name('dashboard');
    Route::get('/tasks', [EarningTaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks/{task}/start', [EarningTaskController::class, 'start'])->name('tasks.start');
    Route::get('/tasks/{task}/run', [EarningTaskController::class, 'run'])->name('tasks.run');
    Route::post('/tasks/{task}/opened', [EarningTaskController::class, 'opened'])->name('tasks.opened');
    Route::post('/tasks/{task}/fail', [EarningTaskController::class, 'fail'])->name('tasks.fail');
    Route::post('/tasks/{task}/complete', [EarningTaskController::class, 'complete'])->name('tasks.complete');

    Route::get('/earnings', [EarningEarningsController::class, 'index'])->name('earnings.index');
    Route::get('/wallet', [EarningPayoutController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/accounts', [EarningPayoutController::class, 'storeAccount'])->name('wallet.accounts.store');
    Route::put('/wallet/accounts/{account}', [EarningPayoutController::class, 'updateAccount'])->name('wallet.accounts.update');
    Route::delete('/wallet/accounts/{account}', [EarningPayoutController::class, 'destroyAccount'])->name('wallet.accounts.destroy');
    Route::post('/wallet/payouts', [EarningPayoutController::class, 'storePayout'])->name('wallet.payouts.store');

    Route::get('/announcements', [EarningAnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/notifications', [EarningNotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/read', [EarningNotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [EarningNotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/profile', [EarningProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [EarningProfileController::class, 'update'])->name('profile.update');

    Route::get('/email/verify', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::middleware(['business.owner'])->prefix('business')->name('business.')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::get('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

        Route::middleware('subscribed')->group(function () {
            Route::get('/products', [ProductController::class, 'index'])->name('products.index');
            Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
            Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
            Route::get('/team-users', [TeamUserController::class, 'index'])->name('team-users.index');
            Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
            Route::get('/low-stock', LowStockController::class)->name('low-stock.index');
            Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
            Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
            Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
            Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
            Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
            Route::post('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');

            Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
            Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
            Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
            Route::post('/sales/{sale}/pay', [SaleController::class, 'pay'])->name('sales.pay');
            Route::post('/sales/scan-barcode', [ScanBarcodeController::class, '__invoke'])
                ->name('sales.scan-barcode');

            Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
            Route::get('/invoices/{invoice}/receipt', [InvoiceController::class, 'receipt'])->name('invoices.receipt');
            Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'thermal'])->name('invoices.print');

            Route::get('/returns', [ReturnController::class, 'index'])->name('returns.index');
            Route::get('/sales/{sale}/returns/create', [ReturnController::class, 'create'])->name('returns.create');
            Route::post('/sales/{sale}/returns', [ReturnController::class, 'store'])->name('returns.store');

            Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
            Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');

            Route::get('/reports', ReportController::class)->name('reports.index');
        });
    });

    Route::middleware(['super.admin'])->group(function () {
        Route::get('/admin/payment-proofs/{paymentProof}/download', PaymentProofDownloadController::class)
            ->name('admin.payment-proofs.download');
    });
});
