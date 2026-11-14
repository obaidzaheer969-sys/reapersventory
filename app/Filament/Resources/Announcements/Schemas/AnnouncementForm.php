<?php

namespace App\Filament\Resources\Announcements\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Announcement')->columns(2)->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('body')
                    ->required()
                    ->rows(6)
                    ->columnSpanFull(),
                Hidden::make('created_by')->default(fn () => auth()->id()),
            ]),
            Section::make('Visibility')->columns(2)->schema([
                Toggle::make('is_active')
                    ->label('Published')
                    ->default(true)
                    ->helperText('Inactive announcements users ko nahi dikhengi.'),
                Toggle::make('is_pinned')
                    ->label('Pin to top')
                    ->default(false),
                DateTimePicker::make('published_at')
                    ->label('Publish at')
                    ->default(fn () => now()),
                DateTimePicker::make('expires_at')
                    ->label('Expires at (optional)'),
            ]),
        ]);
    }
}
