<?php

namespace App\Filament\Resources\Businesses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BusinessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('email')->email()->required(),
                TextInput::make('phone')->tel()->maxLength(50),
                TextInput::make('address')->maxLength(255)->columnSpanFull(),
                TextInput::make('city')->maxLength(100),
                TextInput::make('country')->maxLength(100),
                TextInput::make('timezone')->required()->default('Asia/Karachi'),
                TextInput::make('currency')->required()->maxLength(3)->default('PKR'),
                Toggle::make('is_active')->default(true),
            ]),
        ]);
    }
}
