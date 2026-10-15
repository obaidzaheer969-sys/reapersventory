<?php

namespace App\Livewire\Concerns;

use Illuminate\Validation\ValidationException;

trait RefreshesAfterSave
{
    protected function redirectAndRefresh(string $url): void
    {
        // Full browser navigation so lists/stock always reload (SPA navigate can look stale).
        $this->redirect($url, navigate: false);
    }

    protected function addValidationException(ValidationException $e, string $fallbackField = 'name'): void
    {
        foreach ($e->errors() as $field => $messages) {
            $target = $field === 'limit' || $field === 'subscription' ? $fallbackField : $field;

            foreach ($messages as $message) {
                $this->addError($target, $message);
            }
        }
    }
}
