<?php

namespace App\Filament\Resources\EarningTasks\Schemas;

use App\Enums\TaskType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class EarningTaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Task details')->columns(2)->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('task_type')
                    ->label('Task type')
                    ->options(TaskType::options())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        if ($state === null) {
                            return;
                        }

                        $set('duration_seconds', TaskType::from($state)->defaultSeconds());
                    }),
                TextInput::make('reward')
                    ->label('Reward (PKR)')
                    ->numeric()
                    ->required()
                    ->minValue(2)
                    ->maxValue(2)
                    ->default(2)
                    ->step(0.01)
                    ->prefix('PKR')
                    ->helperText('Admin tasks ka reward fixed 2 PKR hai.'),
                TextInput::make('target_url')
                    ->label('Target URL')
                    ->url()
                    ->required()
                    ->maxLength(2048)
                    ->columnSpanFull()
                    ->helperText('YouTube/Facebook video URLs isi page par official player me chalenge; monetized videos par ads YouTube apne rules se chalata hai. Baaki links naye tab me khulenge.'),
                TextInput::make('duration_seconds')
                    ->label('Required time (seconds)')
                    ->numeric()
                    ->required()
                    ->minValue(5)
                    ->maxValue(3600)
                    ->default(15)
                    ->helperText('Follow/subscribe tasks ke liye 15 seconds; video aur ad watch ke liye admin duration.'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
                Textarea::make('instructions')
                    ->rows(4)
                    ->columnSpanFull(),
            ]),
            Section::make('Visibility')->columns(2)->schema([
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive tasks users ko nahi dikhenge.'),
            ]),
        ]);
    }
}
