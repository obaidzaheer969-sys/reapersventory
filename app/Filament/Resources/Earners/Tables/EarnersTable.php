<?php

namespace App\Filament\Resources\Earners\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EarnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (User $record) => $record->email),
                TextColumn::make('wallet_balance')
                    ->label('Wallet')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('completed_tasks_count')
                    ->label('Tasks done')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_earned')
                    ->label('Total earned')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('total_watched_seconds')
                    ->label('Watched')
                    ->suffix('s')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payout_requests_count')
                    ->label('Payouts')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                IconColumn::make('google_id')
                    ->label('Google')
                    ->boolean()
                    ->getStateUsing(fn (User $record) => filled($record->google_id))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                Action::make('viewStats')
                    ->label('View')
                    ->icon('heroicon-o-chart-bar')
                    ->modalHeading(fn (User $record) => $record->name)
                    ->modalDescription(fn (User $record) => $record->email)
                    ->modalContent(fn (User $record) => view('filament.earner-summary', ['user' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Action::make('toggleActive')
                    ->label(fn (User $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon('heroicon-o-power')
                    ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->update(['is_active' => ! $record->is_active])),
            ]);
    }
}
