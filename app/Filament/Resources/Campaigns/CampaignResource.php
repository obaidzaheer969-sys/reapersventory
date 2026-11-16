<?php

namespace App\Filament\Resources\Campaigns;

use App\Enums\CampaignStatus;
use App\Filament\Resources\Campaigns\Pages\ListCampaigns;
use App\Filament\Resources\Campaigns\Tables\CampaignsTable;
use App\Models\EarningTask;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CampaignResource extends Resource
{
    protected static ?string $model = EarningTask::class;

    protected static ?string $slug = 'campaigns';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Earning';

    protected static ?string $navigationLabel = 'Campaigns';

    protected static ?string $modelLabel = 'campaign';

    protected static ?string $pluralModelLabel = 'campaigns';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->campaigns()
            ->with('owner');
    }

    public static function table(Table $table): Table
    {
        return CampaignsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaigns::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::query()
            ->where('campaign_status', CampaignStatus::Pending->value)
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
