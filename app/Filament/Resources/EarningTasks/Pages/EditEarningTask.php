<?php

namespace App\Filament\Resources\EarningTasks\Pages;

use App\Filament\Resources\EarningTasks\EarningTaskResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEarningTask extends EditRecord
{
    protected static string $resource = EarningTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
