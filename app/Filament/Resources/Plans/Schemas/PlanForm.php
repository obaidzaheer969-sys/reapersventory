<?php

namespace App\Filament\Resources\Plans\Schemas;

use App\Enums\PlanFeatureKey;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('slug')->maxLength(255)->unique(ignoreRecord: true),
                        Textarea::make('description')->columnSpanFull()->rows(3),
                        TextInput::make('price_monthly')->numeric()->required()->prefix('PKR'),
                        TextInput::make('price_yearly')->numeric()->required()->prefix('PKR'),
                        TextInput::make('currency')->required()->maxLength(3)->default('PKR'),
                        TextInput::make('trial_days')->numeric()->minValue(0)->default(0),
                        TextInput::make('sort_order')->numeric()->default(0),
                        Toggle::make('is_active')->default(true),
                        Toggle::make('is_featured')->default(false),
                    ]),
                Section::make('Feature limits')
                    ->schema([
                        Repeater::make('features')
                            ->relationship()
                            ->columns(3)
                            ->schema([
                                Select::make('feature_key')
                                    ->options(collect(PlanFeatureKey::cases())->mapWithKeys(
                                        fn (PlanFeatureKey $key) => [$key->value => $key->label()]
                                    ))
                                    ->required()
                                    ->distinct(),
                                TextInput::make('limit_value')
                                    ->numeric()
                                    ->nullable()
                                    ->helperText('Leave empty for unlimited'),
                                Toggle::make('is_enabled')->default(true),
                            ])
                            ->defaultItems(0)
                            ->collapsible(),
                    ]),
            ]);
    }
}
