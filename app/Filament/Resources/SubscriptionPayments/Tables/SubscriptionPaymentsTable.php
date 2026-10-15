<?php

namespace App\Filament\Resources\SubscriptionPayments\Tables;

use App\Enums\PaymentStatus;
use App\Models\SubscriptionPayment;
use App\Services\PaymentApprovalService;
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

class SubscriptionPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('business.name')->searchable()->sortable(),
                TextColumn::make('plan.name'),
                TextColumn::make('paymentMethod.name')->label('Method'),
                TextColumn::make('amount')->money(fn ($record) => $record->currency),
                TextColumn::make('billing_interval')->badge(),
                TextColumn::make('status')->badge()
                    ->color(fn (PaymentStatus $state) => match ($state) {
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Approved => 'success',
                        PaymentStatus::Rejected => 'danger',
                    }),
                TextColumn::make('period_start')->dateTime(),
                TextColumn::make('period_end')->dateTime(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(collect(PaymentStatus::cases())->mapWithKeys(
                    fn (PaymentStatus $status) => [$status->value => $status->label()]
                )),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('viewProof')
                    ->label('Proof')
                    ->icon('heroicon-o-document')
                    ->visible(fn (SubscriptionPayment $record) => $record->proofs()->exists())
                    ->url(function (SubscriptionPayment $record) {
                        $proof = $record->proofs()->first();

                        return $proof
                            ? route('admin.payment-proofs.download', $proof)
                            : null;
                    })
                    ->openUrlInNewTab(),
                Action::make('approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn (SubscriptionPayment $record) => $record->status === PaymentStatus::Pending)
                    ->form([
                        Textarea::make('review_notes')->label('Review notes')->rows(3),
                    ])
                    ->action(function (SubscriptionPayment $record, array $data, PaymentApprovalService $approver): void {
                        try {
                            $approver->approve($record, Auth::user(), $data['review_notes'] ?? null);

                            Notification::make()
                                ->title('Payment approved')
                                ->success()
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('Cannot approve')
                                ->body(collect($e->errors())->flatten()->first() ?: 'Approval failed.')
                                ->danger()
                                ->send();
                        } catch (Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title('Approve failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->visible(fn (SubscriptionPayment $record) => $record->status === PaymentStatus::Pending)
                    ->form([
                        Textarea::make('review_notes')->label('Rejection reason')->rows(3)->required(),
                    ])
                    ->action(function (SubscriptionPayment $record, array $data, PaymentApprovalService $approver): void {
                        try {
                            $approver->reject($record, Auth::user(), $data['review_notes'] ?? null);

                            Notification::make()
                                ->title('Payment rejected')
                                ->danger()
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('Cannot reject')
                                ->body(collect($e->errors())->flatten()->first() ?: 'Reject failed.')
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
