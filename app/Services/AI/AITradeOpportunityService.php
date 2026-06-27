<?php

namespace App\Services\AI;

use App\Models\AITradeOpportunity;
use App\Models\B2BCompany;
use App\Models\B2BRfq;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AITradeOpportunityService
{
    public function detectForCompany(B2BCompany $company): Collection
    {
        $rfqByCountry = B2BRfq::query()
            ->select('destination_country', DB::raw('count(*) as aggregate'))
            ->whereNotNull('destination_country')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('destination_country')
            ->orderByDesc('aggregate')
            ->limit(3)
            ->get();

        $opportunities = collect();

        foreach ($rfqByCountry as $row) {
            $opportunities->push(AITradeOpportunity::query()->create([
                'company_id' => $company->id,
                'user_id' => $company->user_id,
                'provider' => null,
                'model' => null,
                'confidence_score' => 72,
                'metadata' => ['open_rfqs' => $row->aggregate],
                'opportunity_type' => 'export_destination',
                'title' => 'Growing RFQ demand in ' . $row->destination_country,
                'summary' => 'Recent RFQ activity suggests stronger demand from this market for the next sourcing cycle.',
                'market_country' => $row->destination_country,
                'opportunity_score' => min(100, 45 + ($row->aggregate * 6)),
                'estimated_revenue_increase' => $row->aggregate * 250,
                'estimated_savings' => 0,
            ]));
        }

        $newSuppliers = B2BCompany::query()
            ->publicSuppliers()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $opportunities->push(AITradeOpportunity::query()->create([
            'company_id' => $company->id,
            'user_id' => $company->user_id,
            'provider' => null,
            'model' => null,
            'confidence_score' => 68,
            'metadata' => ['new_suppliers' => $newSuppliers],
            'opportunity_type' => 'new_suppliers',
            'title' => 'Supplier discovery window',
            'summary' => 'New approved suppliers entered the marketplace recently, creating room to compare cost and lead times.',
            'market_country' => null,
            'opportunity_score' => min(100, 40 + ($newSuppliers * 8)),
            'estimated_revenue_increase' => 0,
            'estimated_savings' => $newSuppliers * 75,
        ]));

        return $opportunities;
    }
}
