<?php

namespace App\Services\AI;

use App\Models\AISupplierRisk;
use App\Models\B2BCompany;
use App\Models\B2BFinanceDispute;
use App\Models\B2BFinanceRefund;
use App\Models\User;

class AISupplierRiskService
{
    public function __construct(protected AICommercialAssistant $assistant)
    {
    }

    public function assess(B2BCompany $supplier, ?User $user = null, ?int $companyId = null): AISupplierRisk
    {
        $supplier->loadMissing(['supplierShipments', 'supplierPurchaseOrders', 'supplierQuotations']);

        $lateShipments = $supplier->supplierShipments->filter(function ($shipment) {
            return $shipment->estimated_arrival && $shipment->delivered_at && $shipment->delivered_at->gt($shipment->estimated_arrival);
        })->count();

        $refundCount = B2BFinanceRefund::query()
            ->whereHasMorph('reference', ['*'], function ($query) use ($supplier) {
                $query->where('supplier_company_id', $supplier->id);
            })
            ->count();

        $disputeCount = B2BFinanceDispute::query()
            ->where('supplier_company_id', $supplier->id)
            ->count();

        $tradeVolume = (float) $supplier->supplierPurchaseOrders()->sum('total_amount');
        $companyAge = max(now()->year - (int) ($supplier->year_established ?: now()->year), 0);
        $countryRisk = $this->countryRisk((string) $supplier->country);
        $verificationPenalty = $supplier->verification_status === 'approved' ? 0 : 18;
        $responsePenalty = max(0, 20 - ((float) $supplier->response_rate / 5));
        $shipmentPenalty = min($lateShipments * 6, 18);
        $refundPenalty = min($refundCount * 5, 15);
        $disputePenalty = min($disputeCount * 6, 18);
        $tradeVolumeOffset = $tradeVolume > 50000 ? -10 : ($tradeVolume > 10000 ? -5 : 0);
        $ageOffset = $companyAge >= 5 ? -6 : ($companyAge >= 2 ? -2 : 4);
        $profileOffset = $supplier->profile_score >= 80 ? -6 : ($supplier->profile_score >= 50 ? -2 : 4);

        $riskScore = (int) max(min(round(
            35 + $countryRisk + $verificationPenalty + $responsePenalty + $shipmentPenalty + $refundPenalty + $disputePenalty + $ageOffset + $profileOffset + $tradeVolumeOffset
        ), 100), 0);

        $riskLevel = $this->riskLevel($riskScore);
        $explanation = sprintf(
            'Risk is driven by verification status, %.2f%% response rate, %d late shipments, %d disputes, %d refunds, %s country exposure, and company age.',
            (float) $supplier->response_rate,
            $lateShipments,
            $disputeCount,
            $refundCount,
            $supplier->country ?: 'unknown'
        );

        $enrichment = $this->assistant->enrich('b2b_supplier_risk', [
            'supplier_risk_json' => json_encode([
                'company_name' => $supplier->company_name,
                'risk_score' => $riskScore,
                'risk_level' => $riskLevel,
                'country' => $supplier->country,
                'response_rate' => $supplier->response_rate,
                'late_shipments' => $lateShipments,
                'disputes' => $disputeCount,
                'refunds' => $refundCount,
                'trade_volume' => $tradeVolume,
            ]),
        ], [
            'user' => $user,
            'company_id' => $companyId,
            'metadata' => ['supplier_company_id' => $supplier->id],
        ]);

        if (!empty($enrichment['content'])) {
            $explanation = trim((string) $enrichment['content']);
        }

        return AISupplierRisk::query()->create([
            'company_id' => $companyId,
            'user_id' => $user?->id,
            'provider' => $enrichment['provider'],
            'model' => $enrichment['model'],
            'confidence_score' => round(70 + min($supplier->supplierShipments->count(), 10) + min($supplier->supplierPurchaseOrders->count(), 10), 2),
            'metadata' => [
                'late_shipments' => $lateShipments,
                'refund_count' => $refundCount,
                'dispute_count' => $disputeCount,
                'trade_volume' => $tradeVolume,
                'company_age' => $companyAge,
                'used_ai' => $enrichment['used_ai'],
            ],
            'supplier_company_id' => $supplier->id,
            'subject_user_id' => $supplier->user_id,
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'explanation' => $explanation,
        ]);
    }

    protected function countryRisk(string $country): int
    {
        return match (strtolower($country)) {
            'afghanistan', 'sudan', 'yemen', 'syria' => 18,
            'pakistan', 'nigeria', 'bangladesh' => 9,
            'china', 'india', 'vietnam', 'turkey' => 6,
            'germany', 'france', 'united states', 'usa', 'japan' => 2,
            default => 5,
        };
    }

    protected function riskLevel(int $score): string
    {
        return match (true) {
            $score >= 75 => 'critical',
            $score >= 55 => 'high',
            $score >= 30 => 'medium',
            default => 'low',
        };
    }
}
