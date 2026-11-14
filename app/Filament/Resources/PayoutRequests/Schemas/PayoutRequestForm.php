<?php

namespace App\Filament\Resources\PayoutRequests\Schemas;

use App\Enums\PayoutProvider;
use App\Enums\PayoutStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PayoutRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Request')->columns(2)->schema([
                Select::make('user_id')->relationship('user', 'name')->disabled(),
                Select::make('payout_account_id')->relationship('account', 'id')->disabled(),
                Select::make('provider')->options(PayoutProvider::options())->disabled(),
                TextInput::make('account_name')->disabled(),
                TextInput::make('account_number')->disabled(),
                TextInput::make('amount')->numeric()->prefix('PKR')->disabled(),
                Select::make('status')->options(collect(PayoutStatus::cases())->mapWithKeys(
                    fn (PayoutStatus $status) => [$status->value => $status->label()]
                ))->disabled(),
                TextInput::make('processor.name')->label('Processed by')->disabled(),
                DateTimePicker::make('processed_at')->disabled(),
                Textarea::make('admin_notes')->label('Admin notes')->rows(3)->disabled()->columnSpanFull(),
            ]),
        ]);
    }
}
