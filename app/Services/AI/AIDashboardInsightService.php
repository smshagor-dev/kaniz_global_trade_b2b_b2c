<?php

namespace App\Services\AI;

use App\Models\AIDashboardInsight;
use App\Models\B2BCompany;
use App\Models\B2BFreightQuote;
use App\Models\B2BPaymentTransaction;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BRfq;
use App\Models\B2BShipment;
use App\Models\User;

class AIDashboardInsightService
{
    public function __construct(protected AICommercialAssistant $assistant)
    {
    }

    public function generateForCompany(B2BCompany $company, ?User $user = null): AIDashboardInsight
    {
        $today = now()->toDateString();
        $rfqCount = B2BRfq::query()->where('b2b_company_id', $company->id)->whereDate('created_at', $today)->count();
        $purchaseTotal = (float) B2BPurchaseOrder::query()->where('buyer_company_id', $company->id)->whereDate('created_at', $today)->sum('total_amount');
        $shipmentCount = B2BShipment::query()->where('buyer_company_id', $company->id)->whereDate('created_at', $today)->count();
        $freightCount = B2BFreightQuote::query()->where('buyer_company_id', $company->id)->whereDate('created_at', $today)->count();
        $paymentTotal = (float) B2BPaymentTransaction::query()->where('buyer_company_id', $company->id)->whereDate('created_at', $today)->sum('amount');

        $insights = [
            'today_revenue' => $paymentTotal,
            'purchase_trend' => $purchaseTotal,
            'shipment_trend' => $shipmentCount,
            'freight_trend' => $freightCount,
            'rfq_trend' => $rfqCount,
            'risk_alerts' => [],
            'top_opportunities' => [],
        ];

        $summary = 'Today\'s business summary combines RFQ creation, purchase activity, shipment load, freight requests, and cash movement for a quick operating snapshot.';
        $enrichment = $this->assistant->enrich('b2b_dashboard_insight', [
            'dashboard_json' => json_encode($insights),
        ], [
            'user' => $user,
            'company_id' => $company->id,
        ]);

        if (!empty($enrichment['content'])) {
            $summary = trim((string) $enrichment['content']);
        }

        return AIDashboardInsight::query()->create([
            'company_id' => $company->id,
            'user_id' => $user?->id ?: $company->user_id,
            'provider' => $enrichment['provider'],
            'model' => $enrichment['model'],
            'confidence_score' => 78,
            'metadata' => ['used_ai' => $enrichment['used_ai']],
            'insight_date' => now()->toDateString(),
            'scope' => 'company',
            'title' => 'Executive summary for ' . $company->company_name,
            'summary' => $summary,
            'insights' => $insights,
        ]);
    }
}
