<?php

namespace App\Filament\Resources\EarningTasks\Tables;

use App\Enums\TaskType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EarningTasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->limit(40)
                    ->sortable(),
                TextColumn::make('task_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (TaskType $state) => $state->label())
                    ->color(fn (TaskType $state) => match ($state->platformKey()) {
                        'youtube' => 'danger',
                        'facebook' => 'info',
                        'instagram' => 'warning',
                        'whatsapp' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('target_url')
                    ->label('Target')
                    ->limit(35)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('duration_seconds')
                    ->label('Seconds')
                    ->suffix('s')
                    ->sortable(),
                TextColumn::make('reward')
                    ->label('Reward')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('attempts_count')
                    ->label('Attempts')
                    ->counts('attempts')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('task_type')
                    ->label('Task type')
                    ->options(TaskType::options()),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
