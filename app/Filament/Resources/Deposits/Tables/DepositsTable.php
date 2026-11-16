<?php

namespace App\Filament\Resources\Deposits\Tables;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Services\DepositService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;

class DepositsTable
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
                TextColumn::make('order_id')
                    ->label('Order')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('provider')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('amount')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('credit_amount')
                    ->label('Credited')
                    ->money('PKR')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fee')
                    ->money('PKR')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (DepositStatus $state) => $state->label())
                    ->color(fn (DepositStatus $state) => $state->color())
                    ->sortable(),
                TextColumn::make('cm_tid')
                    ->label('Cashmaal TID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('admin_notes')
                    ->label('Notes')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paid_at')
                    ->label('Paid')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(DepositStatus::options()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Details')
                    ->schema(fn (Schema $schema): Schema => $schema->columns(2)->components([
                        TextEntry::make('user.name')->label('User'),
                        TextEntry::make('user.email')->label('Email'),
                        TextEntry::make('order_id')->label('Order')->copyable(),
                        TextEntry::make('provider')->label('Provider'),
                        TextEntry::make('amount')->label('Amount')->money('PKR'),
                        TextEntry::make('fee')->label('Fee')->money('PKR'),
                        TextEntry::make('credit_amount')->label('Credited')->money('PKR'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (DepositStatus $state) => $state->label())
                            ->color(fn (DepositStatus $state) => $state->color()),
                        TextEntry::make('cm_tid')->label('Cashmaal TID'),
                        TextEntry::make('paid_at')->label('Paid at')->dateTime(),
                        TextEntry::make('created_at')->label('Created')->dateTime(),
                        TextEntry::make('admin_notes')
                            ->label('Admin notes')
                            ->columnSpanFull()
                            ->visible(fn (?Deposit $record) => filled($record?->admin_notes)),
                    ])),
                Action::make('complete')
                    ->label('Mark completed')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Mark deposit as completed')
                    ->modalDescription('Yeh confirm karein ke payment Cashmaal se receive ho gayi hai. Amount user ke wallet me add ho jayega.')
                    ->visible(fn (Deposit $record) => $record->isPending())
                    ->form([
                        Textarea::make('admin_notes')
                            ->label('Notes (optional)')
                            ->rows(2),
                    ])
                    ->action(function (Deposit $record, array $data, DepositService $deposits): void {
                        try {
                            $deposits->complete($record, $record->cm_tid, [
                                'manual_completion' => true,
                            ]);

                            if (filled($data['admin_notes'] ?? null)) {
                                $record->forceFill(['admin_notes' => $data['admin_notes']])->save();
                            }

                            Notification::make()
                                ->title('Deposit completed')
                                ->body('Amount user ke wallet me add kar diya gaya hai.')
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title('Deposit completion failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('fail')
                    ->label('Mark failed')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->visible(fn (Deposit $record) => $record->isPending())
                    ->form([
                        Textarea::make('admin_notes')
                            ->label('Reason')
                            ->rows(2)
                            ->required(),
                    ])
                    ->action(function (Deposit $record, array $data, DepositService $deposits): void {
                        try {
                            $deposits->markFailed($record, (string) ($data['admin_notes'] ?? ''), [
                                'manual_failure' => true,
                            ]);

                            Notification::make()
                                ->title('Deposit marked as failed')
                                ->danger()
                                ->send();
                        } catch (Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title('Update failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->emptyStateHeading('Koi deposit nahi')
            ->emptyStateDescription('Cashmaal se aane wali deposits yahan dikhengi.');
    }
}
