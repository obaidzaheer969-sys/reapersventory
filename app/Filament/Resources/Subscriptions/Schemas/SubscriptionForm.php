<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Enums\BillingInterval;
use App\Enums\SubscriptionStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Select::make('business_id')->relationship('business', 'name')->searchable()->required(),
                Select::make('plan_id')->relationship('plan', 'name')->searchable()->required(),
                Select::make('status')->options(collect(SubscriptionStatus::cases())->mapWithKeys(
                    fn (SubscriptionStatus $status) => [$status->value => $status->label()]
                ))->required(),
                Select::make('billing_interval')->options(collect(BillingInterval::cases())->mapWithKeys(
                    fn (BillingInterval $interval) => [$interval->value => $interval->label()]
                ))->required(),
                DateTimePicker::make('trial_ends_at'),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                DateTimePicker::make('grace_ends_at'),
                DateTimePicker::make('current_period_start'),
                DateTimePicker::make('current_period_end'),
                DateTimePicker::make('cancelled_at'),
                Toggle::make('auto_renew')->default(false),
            ]),
        ]);
    }
}
