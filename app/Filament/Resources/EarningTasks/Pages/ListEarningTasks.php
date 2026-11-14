<?php

namespace App\Filament\Resources\EarningTasks\Pages;

use App\Filament\Resources\EarningTasks\EarningTaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEarningTasks extends ListRecords
{
    protected static string $resource = EarningTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
