<?php

namespace App\Services;

use App\Models\SystemSetting;

class AdService
{
    private const SLOT_KEYS = [
        'top',
        'content',
        'bottom',
        'footer',
    ];

    public function isApproved(): bool
    {
        return (bool) SystemSetting::getValue('site_approved', false);
    }

    public function verificationTag(): string
    {
        if (! $this->isApproved()) {
            return '';
        }

        return trim((string) SystemSetting::getValue('site_verification_tag', ''));
    }

    public function canRunAds(): bool
    {
        return $this->isApproved()
            && $this->verificationTag() !== ''
            && (bool) SystemSetting::getValue('ads_enabled', false);
    }

    public function autoCode(): string
    {
        if (! $this->canRunAds()) {
            return '';
        }

        return trim((string) SystemSetting::getValue('ad_auto_code', ''));
    }

    /**
     * @return array<string, string>
     */
    public function slots(): array
    {
        if (! $this->canRunAds()) {
            return [];
        }

        $slots = [];

        foreach (self::SLOT_KEYS as $slot) {
            $code = trim((string) SystemSetting::getValue('ad_slot_'.$slot, ''));

            if ($code !== '') {
                $slots[$slot] = $code;
            }
        }

        return $slots;
    }
}
