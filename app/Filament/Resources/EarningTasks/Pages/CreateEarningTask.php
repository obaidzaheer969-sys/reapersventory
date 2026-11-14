<?php

namespace App\Filament\Resources\EarningTasks\Pages;

use App\Filament\Resources\EarningTasks\EarningTaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEarningTask extends CreateRecord
{
    protected static string $resource = EarningTaskResource::class;
}
