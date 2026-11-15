<?php

namespace App\Filament\Resources\TaskAttempts\Tables;

use App\Enums\TaskAttemptStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TaskAttemptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('task.title')
                    ->label('Task')
                    ->searchable()
                    ->limit(35),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (TaskAttemptStatus $state) => $state->label())
                    ->color(fn (TaskAttemptStatus $state) => match ($state) {
                        TaskAttemptStatus::Completed => 'success',
                        TaskAttemptStatus::Failed => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('watched_seconds')
                    ->label('Watched')
                    ->suffix('s')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reward_amount')
                    ->label('Reward')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(TaskAttemptStatus::cases())->mapWithKeys(
                        fn (TaskAttemptStatus $status) => [$status->value => $status->label()]
                    )),
                SelectFilter::make('earning_task_id')
                    ->label('Task')
                    ->relationship('task', 'title')
                    ->searchable(),
                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable(),
            ]);
    }
}
