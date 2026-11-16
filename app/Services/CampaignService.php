<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Enums\TaskType;
use App\Enums\UserRole;
use App\Enums\WalletTransactionType;
use App\Models\EarningTask;
use App\Models\User;
use App\Notifications\CampaignStatusNotification;
use App\Notifications\CampaignSubmittedAdminNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

class CampaignService
{
    public function __construct(
        private readonly WalletService $wallet,
    ) {}

    /**
     * @return array{reward: float, total: float, fee: float, duration_seconds: int, quantity: int}
     */
    public function quote(TaskType $type, int $quantity, ?int $durationSeconds = null): array
    {
        $quantity = max(1, $quantity);

        $isWatch = $type->isWatchTask();

        $reward = $isWatch
            ? $this->videoReward($this->normaliseDuration($durationSeconds))
            : (float) config('earning.campaign.action_reward', 0.02);

        $fee = $isWatch ? 0.0 : (float) config('earning.campaign.action_admin_fee', 1);

        return [
            'reward' => round($reward, 2),
            'total' => round(($reward * $quantity) + $fee, 2),
            'fee' => round($fee, 2),
            'duration_seconds' => $isWatch
                ? $this->normaliseDuration($durationSeconds)
                : $type->defaultSeconds(),
            'quantity' => $quantity,
        ];
    }

    /**
     * @param  array{task_type: string, title: string, target_url: string, instructions?: ?string, target_quantity: int, duration_seconds?: ?int}  $data
     */
    public function create(User $user, array $data): EarningTask
    {
        $type = TaskType::from($data['task_type']);

        if (! $type->isCampaignEligible()) {
            throw ValidationException::withMessages([
                'task_type' => 'Yeh task type campaign ke liye available nahi hai.',
            ]);
        }

        $quantity = (int) $data['target_quantity'];
        $this->assertQuantityIsAllowed($quantity);

        $quote = $this->quote($type, $quantity, $data['duration_seconds'] ?? null);
        $total = $quote['total'];

        if ((float) $user->wallet_balance < $total) {
            throw ValidationException::withMessages([
                'amount' => 'Wallet balance kaafi nahi hai. Campaign ke liye '
                    .number_format($total, 2).' PKR chahiye, available '
                    .number_format((float) $user->wallet_balance, 2).' PKR hai.',
            ]);
        }

        $campaign = DB::transaction(function () use ($user, $type, $data, $quote, $total): EarningTask {
            $campaign = EarningTask::query()->create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'task_type' => $type,
                'target_url' => $data['target_url'],
                'duration_seconds' => $quote['duration_seconds'],
                'reward' => $quote['reward'],
                'instructions' => $data['instructions'] ?? null,
                'is_active' => false,
                'sort_order' => 0,
                'campaign_status' => CampaignStatus::Pending,
                'target_quantity' => $quote['quantity'],
                'completed_quantity' => 0,
                'total_cost' => $total,
            ]);

            $this->wallet->debit(
                $user,
                $total,
                WalletTransactionType::CampaignFunding,
                'Campaign payment: '.$campaign->title,
                $campaign,
            );

            return $campaign;
        });

        $this->notifyAdmins(new CampaignSubmittedAdminNotification($campaign));

        return $campaign->refresh();
    }

    public function approve(EarningTask $campaign): EarningTask
    {
        $this->assertPending($campaign);

        return DB::transaction(function () use ($campaign): EarningTask {
            /** @var EarningTask $locked */
            $locked = EarningTask::query()->whereKey($campaign->id)->lockForUpdate()->firstOrFail();

            $this->assertPending($locked);

            $locked->forceFill([
                'campaign_status' => CampaignStatus::Active,
                'is_active' => true,
                'approved_at' => now(),
                'admin_notes' => null,
            ])->save();

            $locked->owner?->notify(new CampaignStatusNotification($locked));

            return $locked->refresh();
        });
    }

    public function reject(EarningTask $campaign, ?string $notes = null): EarningTask
    {
        $this->assertPending($campaign);

        return DB::transaction(function () use ($campaign, $notes): EarningTask {
            /** @var EarningTask $locked */
            $locked = EarningTask::query()->whereKey($campaign->id)->lockForUpdate()->firstOrFail();

            $this->assertPending($locked);

            $refund = (float) $locked->total_cost;

            $locked->forceFill([
                'campaign_status' => CampaignStatus::Rejected,
                'is_active' => false,
                'admin_notes' => $notes,
            ])->save();

            if ($refund > 0) {
                $this->wallet->credit(
                    $locked->owner,
                    $refund,
                    WalletTransactionType::CampaignRefund,
                    'Campaign rejected — refund: '.$locked->title,
                    $locked,
                );
            }

            $locked->owner?->notify(new CampaignStatusNotification($locked));

            return $locked->refresh();
        });
    }

    public function cancel(User $owner, EarningTask $campaign): EarningTask
    {
        if ($campaign->user_id !== $owner->id) {
            abort(403, 'This campaign does not belong to you.');
        }

        if (! $campaign->isCampaign() || $campaign->campaign_status->isFinal()) {
            throw ValidationException::withMessages([
                'campaign' => 'Yeh campaign ab cancel nahi ho sakti.',
            ]);
        }

        return DB::transaction(function () use ($campaign): EarningTask {
            /** @var EarningTask $locked */
            $locked = EarningTask::query()->whereKey($campaign->id)->lockForUpdate()->firstOrFail();

            if ($locked->campaign_status->isFinal()) {
                throw ValidationException::withMessages([
                    'campaign' => 'Yeh campaign ab cancel nahi ho sakti.',
                ]);
            }

            $refund = $locked->campaign_status === CampaignStatus::Pending
                ? (float) $locked->total_cost
                : $locked->budgetRemaining();

            $locked->forceFill([
                'campaign_status' => CampaignStatus::Cancelled,
                'is_active' => false,
            ])->save();

            if ($refund > 0) {
                $this->wallet->credit(
                    $locked->owner,
                    $refund,
                    WalletTransactionType::CampaignRefund,
                    'Campaign cancelled — refund: '.$locked->title,
                    $locked,
                );
            }

            return $locked->refresh();
        });
    }

    private function videoReward(int $durationSeconds): float
    {
        $base = (float) config('earning.campaign.video_base_reward', 2);
        $extra = (float) config('earning.campaign.video_extra_reward', 2);
        $min = (int) config('earning.campaign.video_min_seconds', 60);
        $step = max(1, (int) config('earning.campaign.video_step_seconds', 60));

        $steps = intdiv(max(0, $durationSeconds - $min), $step);

        return round($base + ($steps * $extra), 2);
    }

    private function normaliseDuration(?int $durationSeconds): int
    {
        $min = (int) config('earning.campaign.video_min_seconds', 60);
        $max = (int) config('earning.campaign.video_max_seconds', 3600);
        $step = max(1, (int) config('earning.campaign.video_step_seconds', 60));

        $duration = (int) ($durationSeconds ?? $min);

        if ($duration < $min || $duration > $max || $duration % $step !== 0) {
            throw ValidationException::withMessages([
                'duration_seconds' => 'Video duration '.$min.' se '.$max.' seconds ke darmiyan aur '
                    .$step.' seconds ke multiples me honi chahiye.',
            ]);
        }

        return $duration;
    }

    private function assertQuantityIsAllowed(int $quantity): void
    {
        $min = (int) config('earning.campaign.min_quantity', 100);
        $max = (int) config('earning.campaign.max_quantity', 100000);

        if ($quantity < $min || $quantity > $max) {
            throw ValidationException::withMessages([
                'target_quantity' => 'Quantity '.$min.' se '.$max.' ke darmiyan honi chahiye.',
            ]);
        }
    }

    private function assertPending(EarningTask $campaign): void
    {
        if (! $campaign->isCampaign() || $campaign->campaign_status !== CampaignStatus::Pending) {
            throw ValidationException::withMessages([
                'campaign' => 'Sirf pending campaigns approve ya reject ho sakti hain.',
            ]);
        }
    }

    private function notifyAdmins(object $notification): void
    {
        $admins = User::query()
            ->where('role', UserRole::SuperAdmin->value)
            ->where('is_active', true)
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        try {
            Notification::send($admins, $notification);
        } catch (Throwable) {
            // Notifications must never break campaign creation.
        }
    }
}
