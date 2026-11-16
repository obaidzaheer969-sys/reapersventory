<?php

namespace App\Filament\Resources\EarningTasks;

use App\Filament\Resources\EarningTasks\Pages\CreateEarningTask;
use App\Filament\Resources\EarningTasks\Pages\EditEarningTask;
use App\Filament\Resources\EarningTasks\Pages\ListEarningTasks;
use App\Filament\Resources\EarningTasks\Schemas\EarningTaskForm;
use App\Filament\Resources\EarningTasks\Tables\EarningTasksTable;
use App\Models\EarningTask;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class EarningTaskResource extends Resource
{
    protected static ?string $model = EarningTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Earning';

    protected static ?string $navigationLabel = 'Admin Tasks';

    protected static ?string $modelLabel = 'admin task';

    protected static ?string $pluralModelLabel = 'admin tasks';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return EarningTaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EarningTasksTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->system();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEarningTasks::route('/'),
            'create' => CreateEarningTask::route('/create'),
            'edit' => EditEarningTask::route('/{record}/edit'),
        ];
    }
}
