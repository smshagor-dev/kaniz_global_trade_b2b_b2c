<?php

namespace App\Services\AI\B2B;

use App\Models\B2BCompany;
use App\Models\B2BRfq;
use App\Services\AI\AIManager;
use App\Services\AI\AIPromptService;
use App\Services\AI\AIRequestService;
use Illuminate\Support\Collection;

class SupplierMatchService
{
    public function __construct(
        protected AIManager $manager,
        protected AIRequestService $requestService,
        protected AIPromptService $promptService
    ) {
    }

    public function match(B2BRfq $rfq, int $limit = 5): array
    {
        $rfq->loadMissing(['category', 'product', 'company', 'targetSupplierCompany']);

        $suppliers = B2BCompany::query()
            ->publicSuppliers()
            ->with([
                'categories:id,name',
                'wholesaleProducts:id,user_id,name,category_id',
            ])
            ->withCount(['supplierQuotations', 'supplierShipments'])
            ->get()
            ->map(fn (B2BCompany $supplier) => $this->scoreSupplier($rfq, $supplier))
            ->sortByDesc('score')
            ->values();

        $topMatches = $suppliers->take($limit)->values();

        return [
            'matches' => $topMatches,
            'summary' => $this->buildAiSummary($rfq, $topMatches),
        ];
    }

    protected function scoreSupplier(B2BRfq $rfq, B2BCompany $supplier): array
    {
        $score = 0;
        $reasons = [];

        $rfqCategoryId = (int) ($rfq->category_id ?: $rfq->product?->category_id);
        $categoryIds = $supplier->categories->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($rfqCategoryId > 0 && in_array($rfqCategoryId, $categoryIds, true)) {
            $score += 35;
            $reasons[] = 'Category match';
        }

        $productCategoryIds = $supplier->wholesaleProducts
            ->pluck('category_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        if ($rfqCategoryId > 0 && in_array($rfqCategoryId, $productCategoryIds, true)) {
            $score += 15;
            $reasons[] = 'Relevant wholesale catalog';
        }

        if ((int) $rfq->supplier_company_id === (int) $supplier->id) {
            $score += 25;
            $reasons[] = 'Explicitly targeted supplier';
        }

        if ($supplier->verified_supplier_badge) {
            $score += 10;
            $reasons[] = 'Verified supplier';
        }

        if ($supplier->premium_verified) {
            $score += 8;
            $reasons[] = 'Premium verification';
        }

        if ($supplier->featured_supplier || $supplier->hasActiveFeaturedHomepagePlan()) {
            $score += 8;
            $reasons[] = 'Featured profile';
        }

        $responseRateScore = min((int) round((float) $supplier->response_rate / 10), 10);
        if ($responseRateScore > 0) {
            $score += $responseRateScore;
            $reasons[] = 'Strong response rate';
        }

        $profileScore = min((int) floor(((int) $supplier->profile_score) / 10), 10);
        if ($profileScore > 0) {
            $score += $profileScore;
            $reasons[] = 'High profile score';
        }

        $shipmentScore = min((int) $supplier->supplier_shipments_count, 8);
        if ($shipmentScore > 0) {
            $score += $shipmentScore;
            $reasons[] = 'Shipment history';
        }

        $quotationScore = min((int) $supplier->supplier_quotations_count, 6);
        if ($quotationScore > 0) {
            $score += $quotationScore;
            $reasons[] = 'Quotation activity';
        }

        if ($rfq->destination_country && $supplier->country && strcasecmp((string) $rfq->destination_country, (string) $supplier->country) !== 0) {
            $score += 3;
            $reasons[] = 'Cross-border sourcing capable';
        }

        return [
            'supplier' => $supplier,
            'score' => $score,
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    protected function buildAiSummary(B2BRfq $rfq, Collection $matches): ?array
    {
        if ($matches->isEmpty() || !$this->manager->defaultProvider()) {
            return null;
        }

        try {
            $rendered = $this->promptService->render('b2b_supplier_match_explanation', [
                'rfq_json' => json_encode([
                    'title' => $rfq->title,
                    'description' => $rfq->description,
                    'category' => $rfq->category?->getTranslation('name'),
                    'product' => $rfq->product?->getTranslation('name'),
                    'quantity' => $rfq->quantity,
                    'unit' => $rfq->unit,
                    'destination_country' => $rfq->destination_country,
                    'incoterm' => $rfq->incoterm,
                ]),
                'supplier_candidates_json' => json_encode($matches->take(5)->map(function (array $match) {
                    /** @var B2BCompany $supplier */
                    $supplier = $match['supplier'];

                    return [
                        'company_name' => $supplier->company_name,
                        'country' => $supplier->country,
                        'company_type' => $supplier->company_type,
                        'response_rate' => $supplier->response_rate,
                        'profile_score' => $supplier->profile_score,
                        'score' => $match['score'],
                        'reasons' => $match['reasons'],
                    ];
                })->values()->all()),
            ]);

            $result = $this->requestService->request([
                'module' => 'b2b_supplier_match_explanation',
                'system_prompt' => $rendered['system_prompt'],
                'prompt' => $rendered['user_prompt'],
                'user' => auth()->user(),
                'company_id' => $rfq->b2b_company_id,
                'metadata' => [
                    'rfq_id' => $rfq->id,
                ],
            ]);

            $decoded = json_decode($result['content'], true);

            return is_array($decoded)
                ? $decoded
                : ['summary' => $result['content']];
        } catch (\Throwable $throwable) {
            return null;
        }
    }
}
