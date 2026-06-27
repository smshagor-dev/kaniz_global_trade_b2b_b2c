<?php

namespace App\Services\AI;

use App\Models\AIBuyerRisk;
use App\Models\AICurrencyAnalysis;
use App\Models\AIFreightRecommendation;
use App\Models\AINotificationEvent;
use App\Models\AISupplierRisk;
use App\Models\AITradeOpportunity;
use App\Models\B2BCompany;
use Illuminate\Support\Collection;

class AINotificationService
{
    public function generateForCompany(B2BCompany $company): Collection
    {
        $events = collect();

        $supplierRisk = AISupplierRisk::query()->where('company_id', $company->id)->latest()->first();
        if ($supplierRisk && in_array($supplierRisk->risk_level, ['high', 'critical'], true)) {
            $events->push($this->createEvent($company->id, $company->user_id, 'supplier_risk', 'buyer', $company->id, 'warning', 'High supplier risk detected', $supplierRisk->explanation, AISupplierRisk::class, $supplierRisk->id, $supplierRisk->confidence_score));
        }

        $buyerRisk = AIBuyerRisk::query()->where('company_id', $company->id)->latest()->first();
        if ($buyerRisk && in_array($buyerRisk->risk_level, ['high', 'critical'], true)) {
            $events->push($this->createEvent($company->id, $company->user_id, 'buyer_risk', 'supplier', $company->id, 'warning', 'Buyer trust needs review', $buyerRisk->explanation, AIBuyerRisk::class, $buyerRisk->id, $buyerRisk->confidence_score));
        }

        $currency = AICurrencyAnalysis::query()->where('company_id', $company->id)->latest()->first();
        if ($currency && $currency->volatility_score >= 35) {
            $events->push($this->createEvent($company->id, $company->user_id, 'currency_fluctuation', 'finance', $company->id, 'info', 'Currency volatility alert', $currency->hedging_suggestion, AICurrencyAnalysis::class, $currency->id, $currency->confidence_score));
        }

        $freight = AIFreightRecommendation::query()->where('company_id', $company->id)->latest()->first();
        if ($freight && $freight->cost_saving_estimate > 0) {
            $events->push($this->createEvent($company->id, $company->user_id, 'freight_saving', 'logistics', $company->id, 'success', 'Freight cost saving available', $freight->explanation, AIFreightRecommendation::class, $freight->id, $freight->confidence_score));
        }

        $opportunity = AITradeOpportunity::query()->where('company_id', $company->id)->latest()->first();
        if ($opportunity) {
            $events->push($this->createEvent($company->id, $company->user_id, 'trade_opportunity', 'buyer', $company->id, 'info', 'New trade opportunity identified', $opportunity->summary, AITradeOpportunity::class, $opportunity->id, $opportunity->confidence_score));
        }

        return $events;
    }

    protected function createEvent(?int $companyId, ?int $userId, string $eventType, string $audienceType, ?int $audienceId, string $severity, string $title, ?string $body, ?string $referenceType, ?int $referenceId, float $confidence): AINotificationEvent
    {
        return AINotificationEvent::query()->create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'provider' => null,
            'model' => null,
            'confidence_score' => $confidence,
            'metadata' => [],
            'event_type' => $eventType,
            'audience_type' => $audienceType,
            'audience_id' => $audienceId,
            'severity' => $severity,
            'title' => $title,
            'body' => $body,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'sent_at' => now(),
        ]);
    }
}
