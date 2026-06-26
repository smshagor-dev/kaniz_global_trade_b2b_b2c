<?php

namespace App\Services;

use App\Models\B2BProformaInvoice;

class B2BOrderPlatformFeeService
{
    public const CHARGE_TYPES = ['fixed', 'percentage'];

    public function settings(): array
    {
        $type = (string) get_setting('b2b_order_platform_fee_type', 'percentage');
        if (!in_array($type, self::CHARGE_TYPES, true)) {
            $type = 'percentage';
        }

        return [
            'enabled' => (bool) get_setting('b2b_order_platform_fee_enabled', 0),
            'type' => $type,
            'percent' => (float) get_setting('b2b_order_platform_fee_percent', 0),
            'fixed' => (float) get_setting('b2b_order_platform_fee_fixed', 0),
        ];
    }

    public function calculate(float $orderValue): array
    {
        $settings = $this->settings();
        $orderValue = round(max($orderValue, 0), 2);
        $platformFeeAmount = 0.0;

        if ($settings['enabled']) {
            $platformFeeAmount = $settings['type'] === 'percentage'
                ? round($orderValue * ($settings['percent'] / 100), 2)
                : round($settings['fixed'], 2);
        }

        $platformFeeAmount = min($platformFeeAmount, $orderValue);

        return [
            'enabled' => $settings['enabled'],
            'type' => $settings['type'],
            'percent' => $settings['percent'],
            'fixed' => $settings['fixed'],
            'order_value' => $orderValue,
            'buyer_payable_total' => $orderValue,
            'platform_fee_amount' => $platformFeeAmount,
            'supplier_payout_amount' => round($orderValue - $platformFeeAmount, 2),
        ];
    }

    public function applyToInvoicePayload(array $payload): array
    {
        $charge = $this->calculate((float) ($payload['grand_total'] ?? 0));

        return array_merge($payload, [
            'platform_fee_percent_snapshot' => $charge['enabled'] && $charge['type'] === 'percentage' ? $charge['percent'] : 0,
            'platform_fee_fixed_snapshot' => $charge['enabled'] && $charge['type'] === 'fixed' ? $charge['fixed'] : 0,
            'platform_fee_amount' => $charge['platform_fee_amount'],
            'supplier_payout_amount' => $charge['supplier_payout_amount'],
            'buyer_payable_total' => $charge['buyer_payable_total'],
        ]);
    }

    public function platformRevenue(): float
    {
        return round((float) B2BProformaInvoice::sum('platform_fee_amount'), 2);
    }
}
