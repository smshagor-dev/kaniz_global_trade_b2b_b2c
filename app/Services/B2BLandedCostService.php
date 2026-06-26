<?php

namespace App\Services;

class B2BLandedCostService
{
    public function calculate(array $input): array
    {
        $productCost = (float) ($input['product_cost'] ?? 0);
        $freightCost = (float) ($input['freight_cost'] ?? 0);
        $insurance = (float) ($input['insurance'] ?? 0);
        $duty = (float) ($input['duty'] ?? 0);
        $vatGst = (float) ($input['vat_gst'] ?? 0);
        $customsFee = (float) ($input['customs_fee'] ?? 0);
        $portCharges = (float) ($input['port_charges'] ?? 0);
        $localDelivery = (float) ($input['local_delivery'] ?? 0);
        $quantity = max(1, (int) ($input['quantity'] ?? 1));
        $targetMarginPercent = (float) ($input['target_margin_percent'] ?? 0);

        $totalLandedCost = round(
            $productCost + $freightCost + $insurance + $duty + $vatGst + $customsFee + $portCharges + $localDelivery,
            2
        );

        $costPerUnit = round($totalLandedCost / $quantity, 2);
        $marginEstimate = $targetMarginPercent > 0 ? round($totalLandedCost * ($targetMarginPercent / 100), 2) : 0.0;
        $suggestedSellingPrice = round($totalLandedCost + $marginEstimate, 2);

        return [
            'currency' => $input['currency'] ?? 'USD',
            'total_landed_cost' => $totalLandedCost,
            'cost_per_unit' => $costPerUnit,
            'margin_estimate' => $marginEstimate,
            'suggested_selling_price' => $suggestedSellingPrice,
        ];
    }
}
