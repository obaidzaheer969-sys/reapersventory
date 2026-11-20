<?php

namespace App\Filament\Resources\ApiTokens;

use App\Filament\Resources\ApiTokens\Pages\ManageApiTokens;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Sanctum\PersonalAccessToken;
use UnitEnum;

class ApiTokenResource extends Resource
{
    protected static ?string $model = PersonalAccessToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'API';

    protected static ?string $navigationLabel = 'API tokens';

    protected static ?string $modelLabel = 'API token';

    protected static ?string $pluralModelLabel = 'API tokens';

    protected static ?int $navigationSort = 20;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tokenable.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tokenable.email')
                    ->label('Email')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('abilities')
                    ->formatStateUsing(fn (mixed $state): string => implode(', ', is_array($state) ? $state : [])),
                TextColumn::make('last_used_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Revoke')
                    ->modalHeading('Revoke API token')
                    ->modalDescription('The mobile device using this token will be signed out immediately.')
                    ->successNotificationTitle('API token revoked'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Revoke selected'),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('tokenable');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageApiTokens::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(object $record): bool
    {
        return false;
    }
}
