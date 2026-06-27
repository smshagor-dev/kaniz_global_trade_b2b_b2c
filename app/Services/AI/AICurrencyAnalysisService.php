<?php

namespace App\Services\AI;

use App\Models\AICurrencyAnalysis;
use App\Models\CurrencyRateHistory;
use App\Models\User;
use App\Services\Currency\CurrencyService;

class AICurrencyAnalysisService
{
    public function __construct(
        protected CurrencyService $currencyService,
        protected AICommercialAssistant $assistant
    ) {
    }

    public function analyze(string $currencyCode, float $amount, ?User $user = null, ?int $companyId = null): AICurrencyAnalysis
    {
        $currencyCode = strtoupper($currencyCode);
        $baseCurrency = $this->currencyService->baseCurrency()->code;
        $history = CurrencyRateHistory::query()
            ->where('currency_code', $currencyCode)
            ->latest('synced_at')
            ->limit(7)
            ->pluck('rate');

        $currentRate = (float) $this->currencyService->rateFor($currencyCode);
        $avgRate = $history->avg() ?: $currentRate;
        $volatility = $history->isEmpty()
            ? 5
            : min(100, (int) round(($history->max() - $history->min()) / max($avgRate, 0.0001) * 1000));
        $fxExposure = round(abs($amount * ($currentRate - $avgRate)), 4);
        $profitImpact = round($amount * (($avgRate > 0 ? ($currentRate / $avgRate) : 1) - 1), 4);
        $recommendedCurrency = $volatility > 35 ? $baseCurrency : $currencyCode;
        $hedgingSuggestion = $volatility > 35
            ? 'Use the base or most stable invoice currency and shorten settlement windows.'
            : 'Current volatility is manageable; standard invoicing terms are acceptable.';
        $summary = 'Currency analysis compares recent exchange-rate volatility with the current rate to estimate invoice exposure and margin pressure.';

        $enrichment = $this->assistant->enrich('b2b_currency_analysis', [
            'currency_analysis_json' => json_encode([
                'currency_code' => $currencyCode,
                'base_currency_code' => $baseCurrency,
                'amount' => $amount,
                'volatility_score' => $volatility,
                'fx_exposure' => $fxExposure,
                'profit_impact' => $profitImpact,
                'recommended_invoice_currency' => $recommendedCurrency,
            ]),
        ], [
            'user' => $user,
            'company_id' => $companyId,
        ]);

        if (!empty($enrichment['content'])) {
            $summary = trim((string) $enrichment['content']);
        }

        return AICurrencyAnalysis::query()->create([
            'company_id' => $companyId,
            'user_id' => $user?->id,
            'provider' => $enrichment['provider'],
            'model' => $enrichment['model'],
            'confidence_score' => round(60 + min($history->count() * 5, 30), 2),
            'metadata' => [
                'history_points' => $history->count(),
                'current_rate' => $currentRate,
                'average_rate' => $avgRate,
                'used_ai' => $enrichment['used_ai'],
            ],
            'currency_code' => $currencyCode,
            'base_currency_code' => $baseCurrency,
            'amount' => $amount,
            'volatility_score' => $volatility,
            'fx_exposure' => $fxExposure,
            'recommended_invoice_currency' => $recommendedCurrency,
            'profit_impact' => $profitImpact,
            'hedging_suggestion' => $hedgingSuggestion,
            'summary' => $summary,
        ]);
    }
}
