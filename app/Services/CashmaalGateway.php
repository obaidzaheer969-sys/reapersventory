<?php

namespace App\Services;

use App\Models\Deposit;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Throwable;

class CashmaalGateway
{
    public function isEnabled(): bool
    {
        return (bool) SystemSetting::getValue(
            'cashmaal_enabled',
            (bool) config('earning.deposit.cashmaal.enabled', true),
        );
    }

    public function webId(): string
    {
        return trim((string) SystemSetting::getValue('cashmaal_web_id', ''));
    }

    public function ipnKey(): string
    {
        return trim((string) SystemSetting::getValue('cashmaal_ipn_key', ''));
    }

    public function isConfigured(): bool
    {
        return $this->isEnabled() && filled($this->webId()) && filled($this->ipnKey());
    }

    public function currency(): string
    {
        return (string) config('earning.deposit.cashmaal.currency', 'PKR');
    }

    public function payMethod(): string
    {
        return trim((string) SystemSetting::getValue(
            'cashmaal_pay_method',
            (string) config('earning.deposit.cashmaal.pay_method', ''),
        ));
    }

    public function payUrl(): string
    {
        return (string) config('earning.deposit.cashmaal.pay_url', 'https://cmaal.com/Pay/');
    }

    /**
     * @return array<string, string>
     */
    public function payload(Deposit $deposit): array
    {
        return [
            'pay_method' => $this->payMethod(),
            'amount' => number_format((float) $deposit->amount, 2, '.', ''),
            'currency' => $deposit->currency,
            'succes_url' => route('deposits.cashmaal.success'),
            'cancel_url' => route('deposits.cashmaal.cancel'),
            'client_email' => (string) $deposit->client_email,
            'web_id' => $this->webId(),
            'order_id' => $deposit->order_id,
            'addi_info' => 'Wallet deposit #'.$deposit->id,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function verify(string $cmTid): ?array
    {
        $webId = $this->webId();

        if (blank($cmTid) || blank($webId)) {
            return null;
        }

        try {
            $response = Http::connectTimeout(3)
                ->timeout((int) config('earning.deposit.cashmaal.timeout', 10))
                ->acceptJson()
                ->get((string) config('earning.deposit.cashmaal.verify_url', 'https://api.cmaal.com/verify_v2'), [
                    'CM_TID' => $cmTid,
                    'web_id' => $webId,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            return is_array($data) ? $data : null;
        } catch (Throwable) {
            return null;
        }
    }
}
