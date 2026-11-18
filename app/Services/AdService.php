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

        $usedFingerprints = [];
        $autoCode = $this->autoCode();

        if ($autoCode !== '') {
            foreach ($this->adCodeFingerprints($autoCode) as $fingerprint) {
                $usedFingerprints[$fingerprint] = true;
            }
        }

        $slots = [];

        foreach (self::SLOT_KEYS as $slot) {
            $code = trim((string) SystemSetting::getValue('ad_slot_'.$slot, ''));

            if ($code === '') {
                continue;
            }

            $fingerprints = $this->adCodeFingerprints($code);

            foreach ($fingerprints as $fingerprint) {
                if (isset($usedFingerprints[$fingerprint])) {
                    continue 2;
                }
            }

            foreach ($fingerprints as $fingerprint) {
                $usedFingerprints[$fingerprint] = true;
            }

            $slots[$slot] = $code;
        }

        return $slots;
    }

    /**
     * Ad networks serve a zone once per page. Reusing a zone or container ID
     * in multiple slots makes the later scripts render empty placeholders.
     *
     * @return array<int, string>
     */
    private function adCodeFingerprints(string $code): array
    {
        $fingerprints = [];

        if (preg_match_all('~https?://[^/\s"\']+/(\d+)/([0-9a-f]{16,})~i', $code, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                $fingerprints[] = 'zone:'.$match[1].':'.$match[2];
            }
        }

        if (preg_match_all('~id=["\']container-([0-9a-f]{16,})["\']~i', $code, $matches) > 0) {
            foreach ($matches[1] as $containerId) {
                $fingerprints[] = 'container:'.$containerId;
            }
        }

        if ($fingerprints === []) {
            $fingerprints[] = 'code:'.preg_replace('/\s+/', ' ', $code);
        }

        return array_values(array_unique($fingerprints));
    }
}
