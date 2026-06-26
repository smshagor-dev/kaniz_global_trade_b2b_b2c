<?php

namespace App\Services;

use App\Models\B2BProformaInvoice;

class B2BEscrowFeeService
{
    public const CHARGE_TYPES = ['fixed', 'percentage'];

    public function settings(): array
    {
        $type = (string) get_setting('b2b_escrow_fee_type', 'percentage');
        if (!in_array($type, self::CHARGE_TYPES, true)) {
            $type = 'percentage';
        }

        return [
            'enabled' => (bool) get_setting('b2b_escrow_fee_enabled', 0),
            'type' => $type,
            'percent' => (float) get_setting('b2b_escrow_fee_percent', 0),
            'fixed' => (float) get_setting('b2b_escrow_fee_fixed', 0),
        ];
    }

    public function calculate(float $orderValue): array
    {
        $settings = $this->settings();
        $orderValue = round(max($orderValue, 0), 2);
        $escrowFeeAmount = 0.0;

        if ($settings['enabled']) {
            $escrowFeeAmount = $settings['type'] === 'percentage'
                ? round($orderValue * ($settings['percent'] / 100), 2)
                : round($settings['fixed'], 2);
        }

        return [
            'enabled' => $settings['enabled'],
            'type' => $settings['type'],
            'percent' => $settings['percent'],
            'fixed' => $settings['fixed'],
            'order_value' => $orderValue,
            'escrow_fee_amount' => $escrowFeeAmount,
            'buyer_payable_total' => round($orderValue + $escrowFeeAmount, 2),
        ];
    }

    public function applyToInvoicePayload(array $payload): array
    {
        $charge = $this->calculate((float) ($payload['grand_total'] ?? 0));

        return array_merge($payload, [
            'escrow_fee_percent_snapshot' => $charge['enabled'] && $charge['type'] === 'percentage' ? $charge['percent'] : 0,
            'escrow_fee_fixed_snapshot' => $charge['enabled'] && $charge['type'] === 'fixed' ? $charge['fixed'] : 0,
            'escrow_fee_amount' => $charge['escrow_fee_amount'],
            'buyer_payable_total' => $charge['buyer_payable_total'],
        ]);
    }

    public function platformRevenue(): float
    {
        return round((float) B2BProformaInvoice::sum('escrow_fee_amount'), 2);
    }
}
