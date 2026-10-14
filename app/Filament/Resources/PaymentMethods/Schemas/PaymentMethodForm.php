<?php

namespace App\Filament\Resources\PaymentMethods\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('code')->required()->maxLength(64)->unique(ignoreRecord: true)
                    ->helperText('e.g. bank_transfer, easypaisa, jazzcash'),
                Textarea::make('instructions')->required()->rows(4)->columnSpanFull(),
                TextInput::make('account_name')->maxLength(255),
                TextInput::make('account_number')->maxLength(255),
                Textarea::make('additional_info')->rows(3)->columnSpanFull(),
                TextInput::make('sort_order')->numeric()->default(0),
                Toggle::make('is_active')->default(true),
            ]),
        ]);
    }
}
