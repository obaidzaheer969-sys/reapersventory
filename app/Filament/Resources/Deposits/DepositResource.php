<?php

namespace App\Filament\Resources\Deposits;

use App\Enums\DepositStatus;
use App\Filament\Resources\Deposits\Pages\ListDeposits;
use App\Filament\Resources\Deposits\Tables\DepositsTable;
use App\Models\Deposit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DepositResource extends Resource
{
    protected static ?string $model = Deposit::class;

    protected static ?string $slug = 'deposits';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|UnitEnum|null $navigationGroup = 'Earning';

    protected static ?string $navigationLabel = 'Deposits';

    protected static ?string $modelLabel = 'deposit';

    protected static ?string $pluralModelLabel = 'deposits';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function table(Table $table): Table
    {
        return DepositsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeposits::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::query()
            ->where('status', DepositStatus::Pending->value)
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
