<?php

namespace App\Filament\Resources\SubscriptionPayments\Schemas;

use App\Enums\BillingInterval;
use App\Enums\PaymentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Select::make('business_id')->relationship('business', 'name')->searchable()->disabled(),
                Select::make('subscription_id')->relationship('subscription', 'id')->disabled(),
                Select::make('plan_id')->relationship('plan', 'name')->disabled(),
                Select::make('payment_method_id')->relationship('paymentMethod', 'name')->disabled(),
                TextInput::make('amount')->numeric()->disabled(),
                TextInput::make('currency')->disabled(),
                Select::make('billing_interval')->options(collect(BillingInterval::cases())->mapWithKeys(
                    fn (BillingInterval $interval) => [$interval->value => $interval->label()]
                ))->disabled(),
                Select::make('status')->options(collect(PaymentStatus::cases())->mapWithKeys(
                    fn (PaymentStatus $status) => [$status->value => $status->label()]
                ))->disabled(),
                DateTimePicker::make('period_start')->disabled(),
                DateTimePicker::make('period_end')->disabled(),
                TextInput::make('reference')->disabled(),
                Textarea::make('notes')->disabled()->columnSpanFull(),
                Textarea::make('review_notes')->columnSpanFull(),
            ]),
        ]);
    }
}
