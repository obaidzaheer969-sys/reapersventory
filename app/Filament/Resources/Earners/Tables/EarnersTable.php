<?php

namespace App\Filament\Resources\Earners\Tables;

use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Services\WalletService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
                Action::make('addWallet')
                    ->label('Add balance')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->modalHeading(fn (User $record) => 'Add wallet balance — '.$record->name)
                    ->modalDescription('This amount will be added directly to the user\'s wallet and shown as "Admin credit" in wallet history.')
                    ->form([
                        TextInput::make('amount')
                            ->label('Amount (PKR)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100000)
                            ->required()
                            ->prefix('PKR'),
                        Textarea::make('description')
                            ->label('Note (optional)')
                            ->rows(2)
                            ->maxLength(255)
                            ->placeholder('e.g. Manual deposit / bonus'),
                    ])
                    ->action(function (User $record, array $data, WalletService $wallet): void {
                        $wallet->credit(
                            $record,
                            (float) $data['amount'],
                            WalletTransactionType::AdminCredit,
                            filled($data['description'] ?? null)
                                ? (string) $data['description']
                                : 'Admin wallet credit',
                        );

                        Notification::make()
                            ->title('Wallet balance added')
                            ->body('PKR '.number_format((float) $data['amount'], 2).' was added to '.$record->name.'\'s wallet. New balance: PKR '.number_format((float) $record->wallet_balance, 2).'.')
                            ->success()
                            ->send();
                    }),
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
                DeleteAction::make()
                    ->label('Delete earner')
                    ->modalHeading(fn (User $record) => 'Delete earner — '.$record->name)
                    ->modalDescription('This earner and their wallet/task/payout history will be permanently deleted. This action cannot be undone.')
                    ->successNotificationTitle('Earner deleted')
                    ->visible(fn (User $record): bool => $record->id !== auth()->id()),
            ]);
    }
}
