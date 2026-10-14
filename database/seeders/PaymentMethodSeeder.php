<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Bank Transfer',
                'code' => 'bank_transfer',
                'instructions' => "Transfer the exact subscription amount to the bank account below.\nUse your business name as the payment reference.",
                'account_name' => 'ReapersVentory Pvt Ltd',
                'account_number' => 'PK00AAAA0000000000000000',
                'additional_info' => 'Bank: Example Bank — Branch: Main',
                'sort_order' => 1,
            ],
            [
                'name' => 'Easypaisa',
                'code' => 'easypaisa',
                'instructions' => "Send payment via Easypaisa to the account below.\nScreenshot the confirmation and upload it as payment proof.",
                'account_name' => 'ReapersVentory',
                'account_number' => '0300-0000000',
                'additional_info' => null,
                'sort_order' => 2,
            ],
            [
                'name' => 'JazzCash',
                'code' => 'jazzcash',
                'instructions' => "Send payment via JazzCash to the account below.\nKeep the transaction ID for your payment submission.",
                'account_name' => 'ReapersVentory',
                'account_number' => '0301-0000000',
                'additional_info' => null,
                'sort_order' => 3,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::query()->updateOrCreate(
                ['code' => $method['code']],
                array_merge($method, ['is_active' => true])
            );
        }
    }
}
