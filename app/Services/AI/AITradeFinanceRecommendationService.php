<?php

namespace App\Services\AI;

use App\Models\AIBuyerRisk;
use App\Models\AISupplierRisk;
use App\Models\AITradeFinanceRecommendation;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BProformaInvoice;
use App\Models\User;

class AITradeFinanceRecommendationService
{
    public function recommendForPurchaseOrder(B2BPurchaseOrder $purchaseOrder, ?User $user = null, ?int $companyId = null): AITradeFinanceRecommendation
    {
        $supplierRisk = AISupplierRisk::query()->where('supplier_company_id', $purchaseOrder->supplier_company_id)->latest()->first();
        $buyerRisk = AIBuyerRisk::query()->where('buyer_company_id', $purchaseOrder->buyer_company_id)->latest()->first();
        $riskScore = (int) round(((int) ($supplierRisk->risk_score ?? 45) + (100 - (int) ($buyerRisk->trust_score ?? 55))) / 2);

        $term = match (true) {
            $riskScore >= 75 => 'Escrow',
            $riskScore >= 60 => 'Letter of Credit',
            $riskScore >= 45 => 'Milestone payment',
            $purchaseOrder->total_amount >= 50000 => 'Split payment',
            default => 'Advance',
        };

        return AITradeFinanceRecommendation::query()->create([
            'company_id' => $companyId ?: $purchaseOrder->buyer_company_id,
            'user_id' => $user?->id,
            'provider' => null,
            'model' => null,
            'confidence_score' => 74,
            'metadata' => [
                'supplier_risk_score' => $supplierRisk->risk_score ?? null,
                'buyer_trust_score' => $buyerRisk->trust_score ?? null,
                'total_amount' => $purchaseOrder->total_amount,
            ],
            'reference_type' => B2BPurchaseOrder::class,
            'reference_id' => $purchaseOrder->id,
            'recommended_term' => $term,
            'risk_score' => $riskScore,
            'explanation' => 'Trade finance terms were selected from buyer trust, supplier risk, and transaction value to reduce settlement exposure.',
        ]);
    }

    public function recommendForInvoice(B2BProformaInvoice $invoice, ?User $user = null, ?int $companyId = null): AITradeFinanceRecommendation
    {
        return $this->recommendForPurchaseOrder($invoice->purchaseOrder, $user, $companyId ?: $invoice->buyer_company_id);
    }
}
