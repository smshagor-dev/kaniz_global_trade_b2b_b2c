<?php

namespace App\Services;

use App\Models\B2BSampleOrder;

class B2BSampleProcessingFeeService
{
    public const CHARGE_TYPES = ['fixed', 'percentage'];

    public function settings(): array
    {
        $type = (string) get_setting('b2b_sample_processing_fee_type', 'fixed');
        if (!in_array($type, self::CHARGE_TYPES, true)) {
            $type = 'fixed';
        }

        return [
            'enabled' => (bool) get_setting('b2b_sample_processing_fee_enabled', 0),
            'type' => $type,
            'percent' => (float) get_setting('b2b_sample_processing_fee_percent', 0),
            'fixed' => (float) get_setting('b2b_sample_processing_fee_fixed', 0),
        ];
    }

    public function calculate(float $sampleSubtotal): array
    {
        $settings = $this->settings();
        $sampleSubtotal = round(max($sampleSubtotal, 0), 2);
        $processingFeeAmount = 0.0;

        if ($settings['enabled']) {
            $processingFeeAmount = $settings['type'] === 'percentage'
                ? round($sampleSubtotal * ($settings['percent'] / 100), 2)
                : round($settings['fixed'], 2);
        }

        return [
            'enabled' => $settings['enabled'],
            'type' => $settings['type'],
            'percent' => $settings['percent'],
            'fixed' => $settings['fixed'],
            'sample_subtotal' => $sampleSubtotal,
            'processing_fee_amount' => $processingFeeAmount,
            'buyer_payable_total' => round($sampleSubtotal + $processingFeeAmount, 2),
        ];
    }

    public function applyToSampleOrderPayload(array $payload): array
    {
        $subtotal = round(
            (float) ($payload['sample_price'] ?? 0)
            + (float) ($payload['shipping_amount'] ?? 0),
            2
        );

        $charge = $this->calculate($subtotal);

        return array_merge($payload, [
            'sample_processing_fee_fixed_snapshot' => $charge['enabled'] && $charge['type'] === 'fixed' ? $charge['fixed'] : 0,
            'sample_processing_fee_amount' => $charge['processing_fee_amount'],
            'total_amount' => $charge['buyer_payable_total'],
        ]);
    }

    public function platformRevenue(): float
    {
        return round(
            (float) B2BSampleOrder::whereNotNull('paid_at')->sum('sample_processing_fee_amount'),
            2
        );
    }

    public function revenueProjection(int $sampleCount): array
    {
        $settings = $this->settings();
        $averageSubtotal = round((float) B2BSampleOrder::selectRaw('AVG(sample_price + shipping_amount) as subtotal_average')->value('subtotal_average'), 2);
        $unitFee = $settings['type'] === 'percentage'
            ? round($averageSubtotal * ($settings['percent'] / 100), 2)
            : $settings['fixed'];

        return [
            'type' => $settings['type'],
            'percent' => $settings['percent'],
            'fee' => $settings['fixed'],
            'unit_fee' => $unitFee,
            'average_subtotal' => $averageSubtotal,
            'sample_count' => $sampleCount,
            'projected_revenue' => round($unitFee * $sampleCount, 2),
        ];
    }
}
