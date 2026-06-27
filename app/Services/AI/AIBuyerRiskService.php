<?php

namespace App\Services\AI;

use App\Models\AIBuyerRisk;
use App\Models\B2BCompany;
use App\Models\B2BFinanceDispute;
use App\Models\B2BFinanceRefund;
use App\Models\B2BPaymentTransaction;
use App\Models\User;

class AIBuyerRiskService
{
    public function __construct(protected AICommercialAssistant $assistant)
    {
    }

    public function assess(B2BCompany $buyer, ?User $user = null, ?int $companyId = null): AIBuyerRisk
    {
        $paymentTransactions = B2BPaymentTransaction::query()
            ->where('buyer_company_id', $buyer->id)
            ->get();

        $refundCount = B2BFinanceRefund::query()
            ->whereHasMorph('reference', ['*'], function ($query) use ($buyer) {
                $query->where('buyer_company_id', $buyer->id);
            })
            ->count();

        $disputeCount = B2BFinanceDispute::query()
            ->where('buyer_company_id', $buyer->id)
            ->count();

        $failedPayments = $paymentTransactions->where('status', 'failed')->count();
        $pendingPayments = $paymentTransactions->where('status', 'pending')->count();
        $paidCount = $paymentTransactions->where('status', 'paid')->count();
        $tradeValue = (float) $buyer->buyerPurchaseOrders()->sum('total_amount');
        $countryPenalty = $this->countryRisk((string) $buyer->country);
        $verificationPenalty = $buyer->verification_status === 'approved' ? 0 : 12;
        $trustScore = (int) max(min(round(
            78
            - ($refundCount * 6)
            - ($disputeCount * 7)
            - ($failedPayments * 5)
            - ($pendingPayments * 2)
            - $countryPenalty
            - $verificationPenalty
            + min($paidCount * 2, 10)
            + ($tradeValue > 50000 ? 8 : ($tradeValue > 10000 ? 4 : 0))
        ), 100), 0);

        $riskLevel = match (true) {
            $trustScore < 25 => 'critical',
            $trustScore < 45 => 'high',
            $trustScore < 70 => 'medium',
            default => 'low',
        };

        $explanation = sprintf(
            'Buyer trust reflects %d paid transactions, %d failed transactions, %d disputes, %d refunds, verification status, and country exposure.',
            $paidCount,
            $failedPayments,
            $disputeCount,
            $refundCount
        );

        $enrichment = $this->assistant->enrich('b2b_buyer_risk', [
            'buyer_risk_json' => json_encode([
                'company_name' => $buyer->company_name,
                'trust_score' => $trustScore,
                'risk_level' => $riskLevel,
                'trade_value' => $tradeValue,
                'paid_count' => $paidCount,
                'failed_payments' => $failedPayments,
                'disputes' => $disputeCount,
                'refunds' => $refundCount,
            ]),
        ], [
            'user' => $user,
            'company_id' => $companyId,
            'metadata' => ['buyer_company_id' => $buyer->id],
        ]);

        if (!empty($enrichment['content'])) {
            $explanation = trim((string) $enrichment['content']);
        }

        return AIBuyerRisk::query()->create([
            'company_id' => $companyId,
            'user_id' => $user?->id,
            'provider' => $enrichment['provider'],
            'model' => $enrichment['model'],
            'confidence_score' => round(65 + min($paymentTransactions->count(), 20), 2),
            'metadata' => [
                'refund_count' => $refundCount,
                'dispute_count' => $disputeCount,
                'failed_payments' => $failedPayments,
                'paid_count' => $paidCount,
                'used_ai' => $enrichment['used_ai'],
            ],
            'buyer_company_id' => $buyer->id,
            'subject_user_id' => $buyer->user_id,
            'trust_score' => $trustScore,
            'risk_level' => $riskLevel,
            'explanation' => $explanation,
        ]);
    }

    protected function countryRisk(string $country): int
    {
        return match (strtolower($country)) {
            'afghanistan', 'sudan', 'yemen', 'syria' => 14,
            'pakistan', 'nigeria', 'bangladesh' => 7,
            default => 3,
        };
    }
}
