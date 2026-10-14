<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('business.name')->searchable()->sortable(),
                TextColumn::make('plan.name')->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('billing_interval')->badge(),
                TextColumn::make('ends_at')->dateTime()->sortable(),
                TextColumn::make('grace_ends_at')->dateTime(),
                TextColumn::make('current_period_end')->dateTime()->label('Period end'),
            ])
            ->filters([
                SelectFilter::make('status'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
