<?php

namespace App\Services;

use App\Models\B2BFreightQuoteCost;
use App\Models\B2BShippingQuote;

class B2BLogisticsChargeService
{
    public const SHIPPING_SCOPE = 'shipping';
    public const CHARGE_TYPES = ['fixed', 'percentage'];

    public function settings(string $scope = self::SHIPPING_SCOPE): array
    {
        $this->assertScope($scope);

        $type = (string) get_setting("b2b_{$scope}_site_charge_type", 'fixed');
        if (!in_array($type, self::CHARGE_TYPES, true)) {
            $type = 'fixed';
        }

        return [
            'enabled' => (bool) get_setting("b2b_{$scope}_site_charge_enabled", 0),
            'type' => $type,
            'percent' => (float) get_setting("b2b_{$scope}_site_charge_percent", 0),
            'fixed' => (float) get_setting("b2b_{$scope}_site_charge_fixed", 0),
        ];
    }

    public function calculate(string $scope, float $subtotal): array
    {
        $settings = $this->settings($scope);
        $subtotal = round(max($subtotal, 0), 2);
        $siteChargeAmount = 0.0;

        if ($settings['enabled']) {
            $siteChargeAmount = $settings['type'] === 'percentage'
                ? round($subtotal * ($settings['percent'] / 100), 2)
                : round($settings['fixed'], 2);
        }

        return [
            'enabled' => $settings['enabled'],
            'type' => $settings['type'],
            'percent' => $settings['percent'],
            'fixed' => $settings['fixed'],
            'subtotal' => $subtotal,
            'site_charge_amount' => $siteChargeAmount,
            'total' => round($subtotal + $siteChargeAmount, 2),
        ];
    }

    public function applyToShippingQuotePayload(array $data): array
    {
        $subtotal = round(
            (float) ($data['shipping_cost'] ?? 0)
            + (float) ($data['insurance_amount'] ?? 0)
            + (float) ($data['customs_estimate'] ?? 0),
            2
        );

        $charge = $this->calculate(self::SHIPPING_SCOPE, $subtotal);

        return array_merge($data, [
            'subtotal_cost' => $charge['subtotal'],
            'site_charge_percent_snapshot' => $charge['enabled'] && $charge['type'] === 'percentage' ? $charge['percent'] : 0,
            'site_charge_fixed_snapshot' => $charge['enabled'] && $charge['type'] === 'fixed' ? $charge['fixed'] : 0,
            'site_charge_amount' => $charge['site_charge_amount'],
            'total_cost' => $charge['total'],
        ]);
    }

    public function recalculateShippingQuote(B2BShippingQuote $quote): B2BShippingQuote
    {
        $payload = $this->applyToShippingQuotePayload($quote->toArray());
        $quote->update($quote->filterPersistable($payload));

        return $quote->fresh();
    }

    public function shippingPlatformRevenue(): float
    {
        return round((float) B2BShippingQuote::sum('site_charge_amount'), 2);
    }

    public function freightPlatformRevenue(): float
    {
        return round((float) B2BFreightQuoteCost::where('cost_type', 'platform_service_fee')->sum('amount'), 2);
    }

    protected function assertScope(string $scope): void
    {
        if ($scope !== self::SHIPPING_SCOPE) {
            abort(500, 'Invalid logistics charge scope.');
        }
    }
}
