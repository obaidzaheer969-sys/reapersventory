<?php

namespace App\Filament\Resources\TaskAttempts;

use App\Filament\Resources\TaskAttempts\Pages\ListTaskAttempts;
use App\Filament\Resources\TaskAttempts\Tables\TaskAttemptsTable;
use App\Models\TaskAttempt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TaskAttemptResource extends Resource
{
    protected static ?string $model = TaskAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Earning';

    protected static ?string $navigationLabel = 'Task activity';

    protected static ?string $modelLabel = 'task attempt';

    protected static ?string $pluralModelLabel = 'task attempts';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return TaskAttemptsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaskAttempts::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
