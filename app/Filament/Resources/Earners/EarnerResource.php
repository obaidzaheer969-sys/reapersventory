<?php

namespace App\Filament\Resources\Earners;

use App\Enums\UserRole;
use App\Filament\Resources\Earners\Pages\ListEarners;
use App\Filament\Resources\Earners\Tables\EarnersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class EarnerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Earning';

    protected static ?string $navigationLabel = 'Earners';

    protected static ?string $modelLabel = 'earner';

    protected static ?string $pluralModelLabel = 'earners';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return EarnersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', UserRole::Earner->value)
            ->withCount(['completedTaskAttempts as completed_tasks_count'])
            ->withSum(['completedTaskAttempts as total_earned'], 'reward_amount')
            ->withSum(['completedTaskAttempts as total_watched_seconds'], 'watched_seconds')
            ->withCount(['payoutRequests as payout_requests_count']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEarners::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
