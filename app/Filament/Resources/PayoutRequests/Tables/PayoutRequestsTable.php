<?php

namespace App\Filament\Resources\PayoutRequests\Tables;

use App\Enums\PayoutProvider;
use App\Enums\PayoutStatus;
use App\Models\PayoutRequest;
use App\Services\PayoutService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

class PayoutRequestsTable
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
                TextColumn::make('provider')
                    ->badge()
                    ->formatStateUsing(fn (PayoutProvider $state) => $state->label())
                    ->color(fn (PayoutProvider $state) => match ($state) {
                        PayoutProvider::Easypaisa => 'success',
                        PayoutProvider::JazzCash => 'danger',
                        PayoutProvider::UPaisa => 'primary',
                        PayoutProvider::NayaPay => 'info',
                    }),
                TextColumn::make('account_name')
                    ->label('Account name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('account_number')
                    ->label('Account number')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Payout')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('gross_amount')
                    ->label('Wallet debit')
                    ->money('PKR')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fee')
                    ->label('Admin fee')
                    ->money('PKR')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (PayoutStatus $state) => $state->label())
                    ->color(fn (PayoutStatus $state) => $state->color())
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('processor.name')
                    ->label('Processed by')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('admin_notes')
                    ->label('Notes')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(PayoutStatus::cases())->mapWithKeys(
                        fn (PayoutStatus $status) => [$status->value => $status->label()]
                    )),
                SelectFilter::make('provider')
                    ->options(PayoutProvider::options()),
            ])
            ->recordActions([
                EditAction::make()->label('View'),
                Action::make('markPaid')
                    ->label('Mark paid')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Mark payout as paid')
                    ->modalDescription('Yeh confirm karein ke payment user ke wallet me bhej di gayi hai.')
                    ->visible(fn (PayoutRequest $record) => $record->isPending())
                    ->form([
                        Textarea::make('admin_notes')
                            ->label('Payment notes (optional)')
                            ->rows(3),
                    ])
                    ->action(function (PayoutRequest $record, array $data, PayoutService $payouts): void {
                        try {
                            $payouts->markPaid($record, Auth::user(), $data['admin_notes'] ?? null);

                            Notification::make()
                                ->title('Payout marked as paid')
                                ->success()
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('Cannot mark paid')
                                ->body(collect($e->errors())->flatten()->first() ?: 'Payment could not be marked as paid.')
                                ->danger()
                                ->send();
                        } catch (Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title('Mark paid failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->visible(fn (PayoutRequest $record) => $record->isPending())
                    ->form([
                        Textarea::make('admin_notes')
                            ->label('Rejection reason')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function (PayoutRequest $record, array $data, PayoutService $payouts): void {
                        try {
                            $payouts->reject($record, Auth::user(), (string) ($data['admin_notes'] ?? ''));

                            Notification::make()
                                ->title('Payout rejected')
                                ->body('Amount has been refunded to the user wallet.')
                                ->danger()
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('Cannot reject')
                                ->body(collect($e->errors())->flatten()->first() ?: 'Payout could not be rejected.')
                                ->danger()
                                ->send();
                        } catch (Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title('Reject failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}
