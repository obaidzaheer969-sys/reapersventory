<?php

namespace App\Filament\Resources\TaskAttempts\Pages;

use App\Filament\Resources\TaskAttempts\TaskAttemptResource;
use Filament\Resources\Pages\ListRecords;

class ListTaskAttempts extends ListRecords
{
    protected static string $resource = TaskAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
