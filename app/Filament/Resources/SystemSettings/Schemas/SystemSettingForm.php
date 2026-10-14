<?php

namespace App\Filament\Resources\SystemSettings\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SystemSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')->required()->unique(ignoreRecord: true),
            TextInput::make('group')->required()->default('general'),
            KeyValue::make('value')->nullable(),
            Textarea::make('description')->rows(2),
        ]);
    }
}
