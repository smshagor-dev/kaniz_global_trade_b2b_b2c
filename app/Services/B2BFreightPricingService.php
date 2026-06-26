<?php

namespace App\Services;

use App\Models\B2BFreightPricingRule;
use App\Models\B2BFreightQuote;

class B2BFreightPricingService
{
    public function matchRule(array|B2BFreightQuote $payload): ?B2BFreightPricingRule
    {
        $data = $payload instanceof B2BFreightQuote ? $payload->toArray() : $payload;

        return B2BFreightPricingRule::query()
            ->where('active', true)
            ->when(!empty($data['forwarder_id']), fn ($query) => $query->where(function ($ruleQuery) use ($data) {
                $ruleQuery->whereNull('forwarder_id')->orWhere('forwarder_id', $data['forwarder_id']);
            }))
            ->when(!empty($data['freight_mode']), fn ($query) => $query->where(function ($ruleQuery) use ($data) {
                $ruleQuery->whereNull('freight_mode')->orWhere('freight_mode', $data['freight_mode']);
            }))
            ->when(!empty($data['service_type']), fn ($query) => $query->where(function ($ruleQuery) use ($data) {
                $ruleQuery->whereNull('service_type')->orWhere('service_type', $data['service_type']);
            }))
            ->when(!empty($data['origin_country']), fn ($query) => $query->where(function ($ruleQuery) use ($data) {
                $ruleQuery->whereNull('origin_country')->orWhere('origin_country', $data['origin_country']);
            }))
            ->when(!empty($data['destination_country']), fn ($query) => $query->where(function ($ruleQuery) use ($data) {
                $ruleQuery->whereNull('destination_country')->orWhere('destination_country', $data['destination_country']);
            }))
            ->when(!empty($data['container_type']), fn ($query) => $query->where(function ($ruleQuery) use ($data) {
                $ruleQuery->whereNull('container_type')->orWhere('container_type', $data['container_type']);
            }))
            ->when(!empty($data['incoterm']), fn ($query) => $query->where(function ($ruleQuery) use ($data) {
                $ruleQuery->whereNull('incoterm')->orWhere('incoterm', $data['incoterm']);
            }))
            ->when(isset($data['cargo_weight']), fn ($query) => $query
                ->where(function ($ruleQuery) use ($data) {
                    $ruleQuery->whereNull('min_weight')->orWhere('min_weight', '<=', $data['cargo_weight']);
                })
                ->where(function ($ruleQuery) use ($data) {
                    $ruleQuery->whereNull('max_weight')->orWhere('max_weight', '>=', $data['cargo_weight']);
                }))
            ->when(isset($data['cargo_volume']), fn ($query) => $query
                ->where(function ($ruleQuery) use ($data) {
                    $ruleQuery->whereNull('min_volume')->orWhere('min_volume', '<=', $data['cargo_volume']);
                })
                ->where(function ($ruleQuery) use ($data) {
                    $ruleQuery->whereNull('max_volume')->orWhere('max_volume', '>=', $data['cargo_volume']);
                }))
            ->orderByDesc('forwarder_id')
            ->orderByDesc('container_type')
            ->orderBy('id')
            ->first();
    }

    public function calculate(array|B2BFreightQuote $payload): ?array
    {
        $data = $payload instanceof B2BFreightQuote ? $payload->toArray() : $payload;
        $rule = $this->matchRule($payload);

        if (!$rule) {
            return null;
        }

        $weight = (float) ($data['cargo_weight'] ?? 0);
        $volume = (float) ($data['cargo_volume'] ?? 0);
        $base = (float) $rule->base_price;
        $weightComponent = $weight * (float) $rule->price_per_kg;
        $volumeComponent = $volume * (float) $rule->price_per_cbm;
        $subtotal = round($base + $weightComponent + $volumeComponent, 2);
        $fuelSurcharge = round($subtotal * ((float) $rule->fuel_surcharge_percent / 100), 2);
        $platformFee = round(($subtotal + $fuelSurcharge) * ((float) $rule->platform_fee_percent / 100), 2) + (float) $rule->platform_fee_fixed;
        $total = round($subtotal + $fuelSurcharge + $platformFee, 2);

        return [
            'rule' => $rule,
            'currency' => $rule->currency,
            'base_freight_cost' => round($subtotal, 2),
            'fuel_surcharge' => $fuelSurcharge,
            'platform_service_fee' => round($platformFee, 2),
            'total_cost' => $total,
        ];
    }
}
