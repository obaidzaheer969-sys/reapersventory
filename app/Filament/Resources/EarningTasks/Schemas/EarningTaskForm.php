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
                    ->minValue(1)
                    ->default(2)
                    ->step(0.01)
                    ->prefix('PKR')
                    ->helperText('The admin can set the reward manually. User campaigns have a fixed reward of 2 PKR.'),
                TextInput::make('target_url')
                    ->label('Target URL')
                    ->url()
                    ->required()
                    ->maxLength(2048)
                    ->columnSpanFull()
                    ->helperText('YouTube/Facebook video URLs play in the official player on this page; YouTube runs ads on monetized videos according to its rules. Other links open in a new tab.'),
                TextInput::make('duration_seconds')
                    ->label('Required time (seconds)')
                    ->numeric()
                    ->required()
                    ->minValue(5)
                    ->maxValue(3600)
                    ->default(15)
                    ->helperText('15 seconds for follow/subscribe tasks; admin-set duration for video and ad watch tasks.'),
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
                    ->helperText('Inactive tasks will not be shown to users.'),
            ]),
        ]);
    }
}
