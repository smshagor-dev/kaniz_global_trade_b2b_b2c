<?php

namespace App\Services;

use App\Models\B2BFreightQuote;
use App\Models\B2BFreightQuoteCost;

class B2BInspectionServiceChargeService
{
    public const CHARGE_TYPES = ['fixed', 'percentage'];
    public const SERVICE_COST_TYPE = 'inspection_service_charge';
    public const BASE_COST_TYPE = 'inspection_fee';

    public function settings(): array
    {
        $type = (string) get_setting('b2b_inspection_service_charge_type', 'fixed');
        if (!in_array($type, self::CHARGE_TYPES, true)) {
            $type = 'fixed';
        }

        return [
            'enabled' => (bool) get_setting('b2b_inspection_service_charge_enabled', 0),
            'type' => $type,
            'percent' => (float) get_setting('b2b_inspection_service_charge_percent', 0),
            'fixed' => (float) get_setting('b2b_inspection_service_charge_fixed', 0),
        ];
    }

    public function calculate(float $inspectionSubtotal): array
    {
        $settings = $this->settings();
        $inspectionSubtotal = round(max($inspectionSubtotal, 0), 2);
        $serviceChargeAmount = 0.0;

        if ($settings['enabled'] && $inspectionSubtotal > 0) {
            $serviceChargeAmount = $settings['type'] === 'percentage'
                ? round($inspectionSubtotal * ($settings['percent'] / 100), 2)
                : round($settings['fixed'], 2);
        }

        return [
            'enabled' => $settings['enabled'],
            'type' => $settings['type'],
            'percent' => $settings['percent'],
            'fixed' => $settings['fixed'],
            'inspection_subtotal' => $inspectionSubtotal,
            'service_charge_amount' => $serviceChargeAmount,
        ];
    }

    public function syncForQuote(B2BFreightQuote $quote): void
    {
        $quote->loadMissing('costs');

        $inspectionSubtotal = round(
            $quote->costs
                ->where('cost_type', self::BASE_COST_TYPE)
                ->sum(fn (B2BFreightQuoteCost $line) => $line->amountInBaseCurrency()),
            2
        );

        $charge = $this->calculate($inspectionSubtotal);

        if ($charge['service_charge_amount'] <= 0) {
            $quote->costs()->where('cost_type', self::SERVICE_COST_TYPE)->delete();
            return;
        }

        $quote->costs()->updateOrCreate(
            ['cost_type' => self::SERVICE_COST_TYPE],
            (new B2BFreightQuoteCost())->filterPersistable([
                'description' => 'Inspection service charge',
                'amount' => $charge['service_charge_amount'],
                'currency' => $quote->base_currency ?: $quote->currency,
                'exchange_rate_snapshot' => 1,
                'payer' => 'platform',
                'is_billable' => true,
                'is_optional' => false,
                'sort_order' => 95,
            ])
        );
    }

    public function platformRevenue(): float
    {
        return round(
            (float) B2BFreightQuoteCost::where('cost_type', self::SERVICE_COST_TYPE)->sum('amount'),
            2
        );
    }

    public function revenueProjection(int $inspectionCount): array
    {
        $settings = $this->settings();
        $averageInspectionFee = round(
            (float) B2BFreightQuoteCost::where('cost_type', self::BASE_COST_TYPE)->avg('amount'),
            2
        );
        $unitCharge = $settings['type'] === 'percentage'
            ? round($averageInspectionFee * ($settings['percent'] / 100), 2)
            : $settings['fixed'];

        return [
            'type' => $settings['type'],
            'percent' => $settings['percent'],
            'fixed' => $settings['fixed'],
            'inspection_count' => $inspectionCount,
            'average_inspection_fee' => $averageInspectionFee,
            'unit_charge' => $unitCharge,
            'projected_revenue' => round($unitCharge * $inspectionCount, 2),
        ];
    }
}
