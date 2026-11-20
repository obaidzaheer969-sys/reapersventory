<?php

use App\Http\Controllers\Api\V1\Admin\CampaignController as AdminCampaignController;
use App\Http\Controllers\Api\V1\Admin\DepositController as AdminDepositController;
use App\Http\Controllers\Api\V1\Admin\EarnerController as AdminEarnerController;
use App\Http\Controllers\Api\V1\Admin\PayoutRequestController as AdminPayoutRequestController;
use App\Http\Controllers\Api\V1\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Api\V1\Admin\TokenController as AdminTokenController;
use App\Http\Controllers\Api\V1\AnnouncementController as EarnerAnnouncementController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CampaignController as EarnerCampaignController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DepositController as EarnerDepositController;
use App\Http\Controllers\Api\V1\EarningsController;
use App\Http\Controllers\Api\V1\NotificationController as EarnerNotificationController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\PayoutAccountController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\TaskController as EarnerTaskController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Api\V1\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/', fn () => response()->json([
        'name' => 'ReapersInventory API',
        'version' => 'v1',
    ]))->name('api.v1.index');

    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'api.active', 'throttle:api'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
    });
});

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'api.active', 'api.earner', 'throttle:api'])
    ->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('api.v1.earner.dashboard');
        Route::get('/tasks', [EarnerTaskController::class, 'index'])->name('api.v1.tasks.index');
        Route::post('/tasks/{task}/start', [EarnerTaskController::class, 'start'])->name('api.v1.tasks.start');
        Route::post('/tasks/{task}/opened', [EarnerTaskController::class, 'opened'])->name('api.v1.tasks.opened');
        Route::post('/tasks/{task}/progress', [EarnerTaskController::class, 'progress'])->name('api.v1.tasks.progress');
        Route::post('/tasks/{task}/complete', [EarnerTaskController::class, 'complete'])->name('api.v1.tasks.complete');

        Route::get('/campaigns', [EarnerCampaignController::class, 'index'])->name('api.v1.campaigns.index');
        Route::post('/campaigns/{campaign}/start', [EarnerCampaignController::class, 'start'])->name('api.v1.campaigns.start');
        Route::post('/campaigns/{campaign}/opened', [EarnerCampaignController::class, 'opened'])->name('api.v1.campaigns.opened');
        Route::post('/campaigns/{campaign}/progress', [EarnerCampaignController::class, 'progress'])->name('api.v1.campaigns.progress');
        Route::post('/campaigns/{campaign}/complete', [EarnerCampaignController::class, 'complete'])->name('api.v1.campaigns.complete');

        Route::get('/earnings', EarningsController::class)->name('api.v1.earnings.index');
        Route::get('/wallet', WalletController::class)->name('api.v1.wallet.show');

        Route::get('/payment-methods', PaymentMethodController::class)->name('api.v1.payment-methods.index');
        Route::post('/deposits', [EarnerDepositController::class, 'store'])->name('api.v1.deposits.store');

        Route::get('/payout-accounts', [PayoutAccountController::class, 'index'])->name('api.v1.payout-accounts.index');
        Route::post('/payout-accounts', [PayoutAccountController::class, 'store'])->name('api.v1.payout-accounts.store');

        Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('api.v1.withdrawals.index');
        Route::post('/withdrawals', [WithdrawalController::class, 'store'])->name('api.v1.withdrawals.store');

        Route::get('/announcements', EarnerAnnouncementController::class)->name('api.v1.announcements.index');

        Route::get('/notifications', [EarnerNotificationController::class, 'index'])->name('api.v1.notifications.index');
        Route::post('/notifications/{notification}/read', [EarnerNotificationController::class, 'markRead'])
            ->name('api.v1.notifications.read');
    });

Route::prefix('v1/admin')
    ->middleware(['auth:sanctum', 'api.active', 'api.super.admin', 'throttle:api'])
    ->group(function (): void {
        Route::get('/earners', [AdminEarnerController::class, 'index'])->name('api.v1.admin.earners.index');

        Route::get('/payout-requests', [AdminPayoutRequestController::class, 'index'])->name('api.v1.admin.payout-requests.index');
        Route::patch('/payout-requests/{payoutRequest}/approve', [AdminPayoutRequestController::class, 'approve'])
            ->name('api.v1.admin.payout-requests.approve');
        Route::patch('/payout-requests/{payoutRequest}/reject', [AdminPayoutRequestController::class, 'reject'])
            ->name('api.v1.admin.payout-requests.reject');

        Route::get('/campaigns', [AdminCampaignController::class, 'index'])->name('api.v1.admin.campaigns.index');
        Route::patch('/campaigns/{campaign}/approve', [AdminCampaignController::class, 'approve'])
            ->name('api.v1.admin.campaigns.approve');
        Route::patch('/campaigns/{campaign}/reject', [AdminCampaignController::class, 'reject'])
            ->name('api.v1.admin.campaigns.reject');

        Route::get('/deposits', [AdminDepositController::class, 'index'])->name('api.v1.admin.deposits.index');
        Route::patch('/deposits/{deposit}/complete', [AdminDepositController::class, 'complete'])
            ->name('api.v1.admin.deposits.complete');
        Route::patch('/deposits/{deposit}/fail', [AdminDepositController::class, 'fail'])
            ->name('api.v1.admin.deposits.fail');

        Route::get('/tokens', [AdminTokenController::class, 'index'])->name('api.v1.admin.tokens.index');
        Route::delete('/tokens/{token}', [AdminTokenController::class, 'destroy'])->name('api.v1.admin.tokens.destroy');

        Route::get('/settings', AdminSettingsController::class)->name('api.v1.admin.settings.show');
    });
