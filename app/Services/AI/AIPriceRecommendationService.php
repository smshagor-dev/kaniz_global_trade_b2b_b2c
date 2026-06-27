<?php

namespace App\Services\AI;

use App\Models\AIPriceRecommendation;
use App\Models\Product;
use App\Models\User;

class AIPriceRecommendationService
{
    public function __construct(protected AICommercialAssistant $assistant)
    {
    }

    public function recommend(array $input, ?User $user = null, ?int $companyId = null): AIPriceRecommendation
    {
        $supplierCost = (float) ($input['supplier_cost'] ?? 0);
        $shippingCost = (float) ($input['shipping_cost'] ?? 0);
        $customsCost = (float) ($input['customs_cost'] ?? 0);
        $taxCost = (float) ($input['tax_cost'] ?? 0);
        $vatCost = (float) ($input['vat_cost'] ?? 0);
        $platformFee = (float) ($input['platform_fee'] ?? 0);
        $profitMargin = max((float) ($input['profit_margin'] ?? 0.2), 0.05);
        $competitionIndex = min(max((float) ($input['competition_index'] ?? 0.5), 0), 1);
        $marketTrend = min(max((float) ($input['market_trend_index'] ?? 0.5), 0), 1);
        $seasonality = min(max((float) ($input['seasonality_index'] ?? 0.5), 0), 1);

        $landedCost = $supplierCost + $shippingCost + $customsCost + $taxCost + $vatCost + $platformFee;
        $trendMultiplier = 1 + (($marketTrend - 0.5) * 0.16) + (($seasonality - 0.5) * 0.10) - (($competitionIndex - 0.5) * 0.12);
        $sellingPrice = round(max($landedCost * (1 + $profitMargin) * $trendMultiplier, $landedCost), 4);
        $minimumProfitablePrice = round(max($landedCost * 1.05, $landedCost + ($landedCost * min($profitMargin, 0.10))), 4);
        $wholesalePrice = round(max($sellingPrice * 0.93, $minimumProfitablePrice), 4);
        $distributorPrice = round(max($sellingPrice * 0.89, $minimumProfitablePrice), 4);
        $country = (string) ($input['country'] ?? '');
        $exportAdjustment = in_array(strtolower($country), ['united states', 'usa', 'germany', 'france', 'spain', 'united kingdom'], true) ? 1.04 : 1.01;
        $exportPrice = round(max($sellingPrice * $exportAdjustment, $minimumProfitablePrice), 4);

        $confidence = $this->confidence($input);
        $explanation = 'Deterministic pricing used landed cost, profit margin, competition, market trend, and seasonality to balance margin and market fit.';

        $enrichment = $this->assistant->enrich('b2b_price_recommendation', [
            'pricing_json' => json_encode([
                'landed_cost' => $landedCost,
                'selling_price' => $sellingPrice,
                'minimum_profitable_price' => $minimumProfitablePrice,
                'wholesale_price' => $wholesalePrice,
                'distributor_price' => $distributorPrice,
                'export_price' => $exportPrice,
                'profit_margin' => $profitMargin,
                'country' => $country,
                'currency' => $input['currency'] ?? null,
            ]),
        ], [
            'user' => $user,
            'company_id' => $companyId,
            'metadata' => ['product_id' => $input['product_id'] ?? null],
        ]);

        if (!empty($enrichment['content'])) {
            $explanation = trim((string) $enrichment['content']);
        }

        return AIPriceRecommendation::query()->create([
            'company_id' => $companyId,
            'user_id' => $user?->id,
            'provider' => $enrichment['provider'],
            'model' => $enrichment['model'],
            'confidence_score' => $confidence,
            'metadata' => [
                'competition_index' => $competitionIndex,
                'market_trend_index' => $marketTrend,
                'seasonality_index' => $seasonality,
                'landed_cost' => $landedCost,
                'used_ai' => $enrichment['used_ai'],
                'product_name' => !empty($input['product_id']) ? Product::query()->whereKey($input['product_id'])->value('name') : null,
            ],
            'product_id' => $input['product_id'] ?? null,
            'country' => $country ?: null,
            'currency' => $input['currency'] ?? null,
            'supplier_cost' => $supplierCost,
            'shipping_cost' => $shippingCost,
            'customs_cost' => $customsCost,
            'tax_cost' => $taxCost,
            'vat_cost' => $vatCost,
            'platform_fee' => $platformFee,
            'selling_price' => $sellingPrice,
            'minimum_profitable_price' => $minimumProfitablePrice,
            'wholesale_price' => $wholesalePrice,
            'distributor_price' => $distributorPrice,
            'export_price' => $exportPrice,
            'profit_margin' => $profitMargin,
            'source' => $enrichment['used_ai'] ? 'ai_enriched' : 'deterministic',
            'explanation' => $explanation,
        ]);
    }

    protected function confidence(array $input): float
    {
        $keys = ['supplier_cost', 'shipping_cost', 'customs_cost', 'tax_cost', 'vat_cost', 'platform_fee', 'profit_margin', 'currency', 'country'];
        $present = collect($keys)->filter(fn ($key) => isset($input[$key]) && $input[$key] !== '')->count();

        return round(55 + (($present / count($keys)) * 40), 2);
    }
}
