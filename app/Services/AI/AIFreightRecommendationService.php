<?php

namespace App\Services\AI;

use App\Models\AIFreightRecommendation;
use App\Models\B2BFreightForwarder;
use App\Models\B2BFreightQuote;
use App\Models\B2BShipment;
use App\Models\User;

class AIFreightRecommendationService
{
    public function __construct(protected AICommercialAssistant $assistant)
    {
    }

    public function recommendForQuote(B2BFreightQuote $quote, ?User $user = null, ?int $companyId = null): AIFreightRecommendation
    {
        $quote->loadMissing(['forwarder', 'originPort', 'destinationPort', 'costs']);

        $forwarders = B2BFreightForwarder::query()
            ->where('is_active', true)
            ->get();

        $recommendedForwarder = $forwarders->sortBy(function (B2BFreightForwarder $forwarder) use ($quote) {
            $amounts = $forwarder->defaultQuoteAmounts();
            $modeFit = in_array($quote->freight_mode, (array) $forwarder->supported_modes, true) ? -100 : 0;

            return ($amounts['total_cost'] ?: 999999) + $modeFit;
        })->first();

        $baseCost = (float) ($quote->total_cost ?: ($quote->freight_cost + $quote->insurance_cost + $quote->customs_estimate));
        $recommendedCost = $recommendedForwarder ? (float) $recommendedForwarder->defaultQuoteAmounts()['total_cost'] : $baseCost;
        $mode = $quote->cargo_weight > 3000 ? 'sea_freight' : ($quote->cargo_weight > 600 ? 'rail_freight' : 'air_freight');
        $strategy = $quote->cargo_volume >= 28 || $quote->container_count >= 1 ? 'FCL' : ($quote->cargo_volume >= 10 ? 'LCL' : 'Mixed');
        $estimatedDays = match ($mode) {
            'air_freight' => 7,
            'rail_freight' => 18,
            'road_freight' => 12,
            default => 28,
        };
        $customsDelay = in_array(strtolower((string) $quote->destination_country), ['bangladesh', 'india', 'nigeria'], true) ? 4 : 2;
        $riskScore = min(100, (int) round(($quote->cargo_weight / 200) + ($customsDelay * 8) + ($mode === 'air_freight' ? 8 : 4)));
        $costSaving = max(round($baseCost - $recommendedCost, 4), 0);
        $carbonEstimate = round(($quote->cargo_weight ?: 1) * match ($mode) {
            'air_freight' => 1.9,
            'rail_freight' => 0.7,
            'road_freight' => 1.1,
            default => 0.5,
        }, 4);
        $confidence = round(66 + ($recommendedForwarder ? 12 : 0) + ($quote->total_cost ? 8 : 0), 2);

        $explanation = 'Recommendation is based on mode fit, forwarder default cost profile, cargo weight, volume, container strategy, and expected customs friction.';

        $enrichment = $this->assistant->enrich('b2b_freight_recommendation', [
            'freight_recommendation_json' => json_encode([
                'quote_number' => $quote->quote_number,
                'recommended_mode' => $mode,
                'recommended_strategy' => $strategy,
                'recommended_forwarder' => $recommendedForwarder?->name,
                'estimated_cost' => $recommendedCost,
                'estimated_days' => $estimatedDays,
                'risk_score' => $riskScore,
            ]),
        ], [
            'user' => $user,
            'company_id' => $companyId,
            'metadata' => ['freight_quote_id' => $quote->id],
        ]);

        if (!empty($enrichment['content'])) {
            $explanation = trim((string) $enrichment['content']);
        }

        return AIFreightRecommendation::query()->create([
            'company_id' => $companyId ?: $quote->buyer_company_id ?: $quote->supplier_company_id,
            'user_id' => $user?->id,
            'provider' => $enrichment['provider'],
            'model' => $enrichment['model'],
            'confidence_score' => $confidence,
            'metadata' => [
                'base_cost' => $baseCost,
                'used_ai' => $enrichment['used_ai'],
                'quote_number' => $quote->quote_number,
            ],
            'freight_quote_id' => $quote->id,
            'shipment_id' => $quote->shipment_id,
            'forwarder_id' => $recommendedForwarder?->id,
            'recommended_mode' => $mode,
            'recommended_strategy' => $strategy,
            'recommended_forwarder_name' => $recommendedForwarder?->name,
            'estimated_delivery_days' => $estimatedDays,
            'estimated_customs_delay_days' => $customsDelay,
            'estimated_shipping_cost' => $recommendedCost,
            'cost_saving_estimate' => $costSaving,
            'carbon_estimate' => $carbonEstimate,
            'risk_score' => $riskScore,
            'explanation' => $explanation,
        ]);
    }

    public function recommendForShipment(B2BShipment $shipment, ?User $user = null, ?int $companyId = null): AIFreightRecommendation
    {
        $quote = B2BFreightQuote::query()
            ->where('shipment_id', $shipment->id)
            ->latest('id')
            ->first();

        if ($quote) {
            return $this->recommendForQuote($quote, $user, $companyId);
        }

        $synthetic = new B2BFreightQuote([
            'shipment_id' => $shipment->id,
            'buyer_company_id' => $shipment->buyer_company_id,
            'supplier_company_id' => $shipment->supplier_company_id,
            'freight_mode' => $shipment->transport_mode ?: 'sea_freight',
            'container_count' => 1,
            'cargo_weight' => (float) ($shipment->total_weight ?: 1000),
            'cargo_volume' => 12,
            'destination_country' => $shipment->destination_country,
            'origin_country' => $shipment->origin_country,
            'freight_cost' => 0,
            'insurance_cost' => 0,
            'customs_estimate' => 0,
            'total_cost' => 0,
        ]);

        return $this->recommendForQuote($synthetic, $user, $companyId ?: $shipment->buyer_company_id ?: $shipment->supplier_company_id);
    }
}
