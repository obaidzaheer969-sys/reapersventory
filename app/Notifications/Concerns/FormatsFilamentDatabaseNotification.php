<?php

namespace App\Notifications\Concerns;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;

trait FormatsFilamentDatabaseNotification
{
    /**
     * Filament admin bell / "All notifications" expects this format.
     *
     * @return array<string, mixed>
     */
    protected function filamentDatabaseMessage(
        string $title,
        string $body,
        ?string $url = null,
        string $status = 'info',
        string $actionLabel = 'Open',
    ): array {
        $notification = FilamentNotification::make()
            ->title($title)
            ->body($body);

        $notification = match ($status) {
            'success' => $notification->success(),
            'warning' => $notification->warning(),
            'danger' => $notification->danger(),
            default => $notification->info(),
        };

        if (filled($url)) {
            $notification->actions([
                Action::make('open')
                    ->label($actionLabel)
                    ->button()
                    ->url($url, shouldOpenInNewTab: false),
            ]);
        }

        return $notification->getDatabaseMessage();
    }
}
