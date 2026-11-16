<?php

namespace App\Filament\Resources\Campaigns\Tables;

use App\Enums\CampaignStatus;
use App\Enums\TaskType;
use App\Models\EarningTask;
use App\Services\CampaignService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use Throwable;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->limit(35)
                    ->sortable(),
                TextColumn::make('owner.name')
                    ->label('User')
                    ->searchable()
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
                    }),
                TextColumn::make('campaign_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (CampaignStatus $state) => $state->label())
                    ->color(fn (CampaignStatus $state) => $state->color())
                    ->sortable(),
                TextColumn::make('completed_quantity')
                    ->label('Completed')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('target_quantity')
                    ->label('Target')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reward')
                    ->label('Per action')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('total_cost')
                    ->label('Budget')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('budget_spent')
                    ->label('Spent')
                    ->state(fn (EarningTask $record) => 'PKR '.number_format($record->budgetSpent(), 2)),
                TextColumn::make('platform_fee')
                    ->label('Platform fee')
                    ->state(fn (EarningTask $record) => 'PKR '.number_format($record->platformFee(), 2))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('target_url')
                    ->label('Target URL')
                    ->limit(40)
                    ->url(fn (EarningTask $record) => $record->target_url, shouldOpenInNewTab: true)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('instructions')
                    ->label('Instructions')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('admin_notes')
                    ->label('Admin notes')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('approved_at')
                    ->label('Approved')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('campaign_status')
                    ->label('Status')
                    ->options(CampaignStatus::options()),
                SelectFilter::make('task_type')
                    ->label('Task type')
                    ->options(TaskType::campaignOptions()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Details')
                    ->schema(fn (Schema $schema): Schema => $schema->columns(2)->components([
                        TextEntry::make('owner.name')->label('User'),
                        TextEntry::make('title')->label('Title')->columnSpanFull(),
                        TextEntry::make('task_type')
                            ->label('Type')
                            ->formatStateUsing(fn (TaskType $state) => $state->label()),
                        TextEntry::make('campaign_status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (CampaignStatus $state) => $state->label())
                            ->color(fn (CampaignStatus $state) => $state->color()),
                        TextEntry::make('target_url')->label('Target URL')->columnSpanFull(),
                        TextEntry::make('duration_seconds')->label('Duration')->suffix(' sec'),
                        TextEntry::make('reward')->label('Reward / action')->money('PKR'),
                        TextEntry::make('target_quantity')->label('Target quantity')->numeric(),
                        TextEntry::make('completed_quantity')->label('Completed quantity')->numeric(),
                        TextEntry::make('total_cost')->label('Total paid')->money('PKR'),
                        TextEntry::make('budget_spent')
                            ->label('Spent')
                            ->state(fn (EarningTask $record) => 'PKR '.number_format($record->budgetSpent(), 2)),
                        TextEntry::make('platform_fee')
                            ->label('Platform fee')
                            ->state(fn (EarningTask $record) => 'PKR '.number_format($record->platformFee(), 2)),
                        TextEntry::make('budget_remaining')
                            ->label('Remaining budget')
                            ->state(fn (EarningTask $record) => 'PKR '.number_format($record->budgetRemaining(), 2)),
                        TextEntry::make('instructions')
                            ->label('Instructions')
                            ->columnSpanFull()
                            ->visible(fn (?EarningTask $record) => filled($record?->instructions)),
                        TextEntry::make('admin_notes')
                            ->label('Admin notes')
                            ->columnSpanFull()
                            ->visible(fn (?EarningTask $record) => filled($record?->admin_notes)),
                        TextEntry::make('approved_at')->label('Approved')->dateTime(),
                        TextEntry::make('created_at')->label('Submitted')->dateTime(),
                    ])),
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Approve campaign')
                    ->modalDescription('Campaign live ho jayegi aur earners ko dikhegi. Payment already wallet se hold hai.')
                    ->visible(fn (EarningTask $record) => $record->campaign_status === CampaignStatus::Pending)
                    ->action(function (EarningTask $record, CampaignService $campaigns): void {
                        try {
                            $campaigns->approve($record);

                            Notification::make()
                                ->title('Campaign approved')
                                ->body('Campaign ab live hai aur earners ko dikhegi.')
                                ->success()
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('Cannot approve campaign')
                                ->body(collect($e->errors())->flatten()->first() ?: 'Campaign approve nahi ho saki.')
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
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Reject campaign')
                    ->modalDescription('Campaign reject hone par poora amount user ke wallet me refund ho jayega.')
                    ->visible(fn (EarningTask $record) => $record->campaign_status === CampaignStatus::Pending)
                    ->form([
                        Textarea::make('admin_notes')
                            ->label('Rejection reason')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function (EarningTask $record, array $data, CampaignService $campaigns): void {
                        try {
                            $campaigns->reject($record, (string) ($data['admin_notes'] ?? ''));

                            Notification::make()
                                ->title('Campaign rejected')
                                ->body('Amount user ke wallet me refund kar diya gaya hai.')
                                ->danger()
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('Cannot reject campaign')
                                ->body(collect($e->errors())->flatten()->first() ?: 'Campaign reject nahi ho saki.')
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
            ])
            ->emptyStateHeading('Koi campaign nahi')
            ->emptyStateDescription('Users ki publish ki hui campaigns yahan dikhengi.');
    }
}
