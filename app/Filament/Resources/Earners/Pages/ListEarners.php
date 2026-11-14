<?php

namespace App\Filament\Resources\Earners\Pages;

use App\Filament\Resources\Earners\EarnerResource;
use Filament\Resources\Pages\ListRecords;

class ListEarners extends ListRecords
{
    protected static string $resource = EarnerResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
