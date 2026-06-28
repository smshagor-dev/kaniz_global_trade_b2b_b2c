<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateCurrencyAnalysisJob;
use App\Jobs\GenerateDashboardInsightJob;
use App\Jobs\GenerateOpportunityJob;
use App\Models\AIBuyerRisk;
use App\Models\AICurrencyAnalysis;
use App\Models\AIDashboardInsight;
use App\Models\AIFreightRecommendation;
use App\Models\AINotificationEvent;
use App\Models\AIPriceRecommendation;
use App\Models\AISupplierRisk;
use App\Models\AITradeFinanceRecommendation;
use App\Models\AITradeOpportunity;
use App\Models\AIRequest;
use App\Models\B2BCompany;
use App\Models\B2BFreightQuote;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BRfq;
use App\Models\B2BShipment;
use App\Services\AI\B2B\DocumentSummaryService;
use App\Services\AI\B2B\HSCodeAssistantService;
use App\Services\AI\B2B\RFQAssistantService;
use App\Services\AI\B2B\SupplierMatchService;
use App\Services\AI\B2B\TradeAssistantService;
use App\Services\AI\AIBuyerRiskService;
use App\Services\AI\AICurrencyAnalysisService;
use App\Services\AI\AIDashboardInsightService;
use App\Services\AI\AIFreightRecommendationService;
use App\Services\AI\AINotificationService;
use App\Services\AI\AIPriceRecommendationService;
use App\Services\AI\AISupplierRiskService;
use App\Services\AI\AITradeFinanceRecommendationService;
use App\Services\AI\AITradeOpportunityService;
use App\Services\B2BGlobalConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class B2BAIController extends Controller
{
    public function __construct(
        protected RFQAssistantService $rfqAssistantService,
        protected SupplierMatchService $supplierMatchService,
        protected HSCodeAssistantService $hsCodeAssistantService,
        protected DocumentSummaryService $documentSummaryService,
        protected TradeAssistantService $tradeAssistantService,
        protected AIPriceRecommendationService $priceRecommendationService,
        protected AISupplierRiskService $supplierRiskService,
        protected AIBuyerRiskService $buyerRiskService,
        protected AITradeOpportunityService $tradeOpportunityService,
        protected AIFreightRecommendationService $freightRecommendationService,
        protected AICurrencyAnalysisService $currencyAnalysisService,
        protected AITradeFinanceRecommendationService $tradeFinanceRecommendationService,
        protected AIDashboardInsightService $dashboardInsightService,
        protected AINotificationService $notificationService,
        protected B2BGlobalConfigService $globalConfigService
    ) {
    }

    public function dashboard()
    {
        $company = $this->activeCompany();
        if (!$company) {
            flash(translate('Select an active B2B company to use AI tools.'))->warning();

            return redirect()->route('b2b.company.show');
        }

        if (!$this->globalConfigService->aiEnabled()) {
            flash(translate('B2B AI tools are currently disabled by the administrator.'))->warning();

            return redirect()->route('b2b.company.show');
        }

        $recentRequests = AIRequest::query()
            ->where('company_id', $company->id)
            ->where('module', 'like', 'b2b_%')
            ->latest()
            ->limit(10)
            ->get();

        $latest = [
            'price' => AIPriceRecommendation::query()->where('company_id', $company->id)->latest()->first(),
            'supplier_risk' => AISupplierRisk::query()->where('company_id', $company->id)->latest()->first(),
            'buyer_risk' => AIBuyerRisk::query()->where('company_id', $company->id)->latest()->first(),
            'freight' => AIFreightRecommendation::query()->where('company_id', $company->id)->latest()->first(),
            'currency' => AICurrencyAnalysis::query()->where('company_id', $company->id)->latest()->first(),
            'finance' => AITradeFinanceRecommendation::query()->where('company_id', $company->id)->latest()->first(),
            'opportunity' => AITradeOpportunity::query()->where('company_id', $company->id)->latest()->first(),
            'insight' => AIDashboardInsight::query()->where('company_id', $company->id)->latest()->first(),
        ];

        $aiSettings = $this->globalConfigService->aiSettings();

        return view('b2b.ai.dashboard', compact('company', 'recentRequests', 'latest', 'aiSettings'));
    }

    public function rfqAssistant(Request $request)
    {
        $company = $this->requireAiToolAccess('rfq_enabled', 'AI RFQ');

        $suggestion = null;
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'title' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'category_id' => ['nullable', 'integer'],
                'product_id' => ['nullable', 'integer'],
                'quantity' => ['nullable', 'numeric'],
                'unit' => ['nullable', 'string', 'max:50'],
                'target_price' => ['nullable', 'numeric'],
                'currency' => ['nullable', 'string', 'max:20'],
                'incoterm' => ['nullable', 'string', 'max:20'],
                'destination_country' => ['nullable', 'string', 'max:100'],
                'destination_city' => ['nullable', 'string', 'max:100'],
            ]);

            try {
                $suggestion = $this->rfqAssistantService->suggest($validated, Auth::user(), $company->id);
            } catch (\Throwable $throwable) {
                flash($throwable->getMessage())->warning();
            }
        }

        return view('b2b.ai.rfq_assistant', compact('company', 'suggestion'));
    }

    public function supplierMatches(int $id)
    {
        $company = $this->requireAiAccess();

        $rfq = B2BRfq::query()
            ->with(['category', 'product', 'company', 'targetSupplierCompany'])
            ->where('b2b_company_id', $company->id)
            ->findOrFail($id);

        $results = $this->supplierMatchService->match($rfq);

        return view('b2b.ai.supplier_matches', [
            'company' => $company,
            'rfq' => $rfq,
            'matches' => $results['matches'],
            'summary' => $results['summary'],
        ]);
    }

    public function hsCode(Request $request)
    {
        $company = $this->requireAiAccess();

        $suggestion = null;
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'query' => ['required', 'string', 'max:1000'],
                'country' => ['nullable', 'string', 'max:100'],
            ]);

            $suggestion = $this->hsCodeAssistantService->suggest($validated, Auth::user(), $company->id);
        }

        return view('b2b.ai.hs_code', compact('company', 'suggestion'));
    }

    public function documentSummary(string $type, int $id)
    {
        $this->requireAiAccess();

        try {
            $summary = $this->documentSummaryService->summarize($type, $id, Auth::user());
        } catch (\Throwable $throwable) {
            flash($throwable->getMessage())->warning();

            return redirect()->back();
        }

        return view('b2b.ai.document_summary', compact('summary', 'type', 'id'));
    }

    public function tradeAssistant(Request $request)
    {
        $company = $this->requireAiToolAccess('negotiation_enabled', 'AI Negotiation');

        $answer = null;
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'question' => ['required', 'string', 'max:4000'],
                'context_type' => ['nullable', 'string', 'max:50'],
                'context_id' => ['nullable', 'integer'],
            ]);

            try {
                $answer = $this->tradeAssistantService->ask(
                    $validated['question'],
                    Auth::user(),
                    $company->id,
                    [
                        'context_type' => $validated['context_type'] ?? null,
                        'context_id' => $validated['context_id'] ?? null,
                    ]
                );
            } catch (\Throwable $throwable) {
                flash($throwable->getMessage())->warning();
            }
        }

        return view('b2b.ai.trade_assistant', compact('company', 'answer'));
    }

    public function priceRecommendation(Request $request)
    {
        $company = $this->requireAiAccess();
        $recommendation = null;

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'product_id' => ['nullable', 'integer'],
                'country' => ['nullable', 'string', 'max:100'],
                'currency' => ['required', 'string', 'max:10'],
                'supplier_cost' => ['required', 'numeric', 'min:0'],
                'shipping_cost' => ['nullable', 'numeric', 'min:0'],
                'customs_cost' => ['nullable', 'numeric', 'min:0'],
                'tax_cost' => ['nullable', 'numeric', 'min:0'],
                'vat_cost' => ['nullable', 'numeric', 'min:0'],
                'platform_fee' => ['nullable', 'numeric', 'min:0'],
                'profit_margin' => ['nullable', 'numeric', 'min:0.01'],
                'competition_index' => ['nullable', 'numeric', 'min:0', 'max:1'],
                'market_trend_index' => ['nullable', 'numeric', 'min:0', 'max:1'],
                'seasonality_index' => ['nullable', 'numeric', 'min:0', 'max:1'],
            ]);

            $recommendation = $this->priceRecommendationService->recommend($validated, Auth::user(), $company->id);
        }

        $history = AIPriceRecommendation::query()->where('company_id', $company->id)->latest()->paginate(10);

        return view('b2b.ai.price_recommendation', compact('company', 'recommendation', 'history'));
    }

    public function supplierRisk(Request $request)
    {
        $company = $this->requireAiAccess();
        $assessment = null;

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'supplier_company_id' => ['required', 'integer'],
            ]);

            $supplier = B2BCompany::query()
                ->publicSuppliers()
                ->findOrFail($validated['supplier_company_id']);

            $assessment = $this->supplierRiskService->assess($supplier, Auth::user(), $company->id);
        }

        $history = AISupplierRisk::query()->where('company_id', $company->id)->latest()->paginate(10);
        $suppliers = B2BCompany::query()->publicSuppliers()->orderBy('company_name')->limit(100)->get(['id', 'company_name']);

        return view('b2b.ai.supplier_risk', compact('company', 'assessment', 'history', 'suppliers'));
    }

    public function buyerRisk(Request $request)
    {
        $company = $this->requireAiAccess();
        $assessment = null;

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'buyer_company_id' => ['required', 'integer'],
            ]);

            $buyer = B2BCompany::query()->findOrFail($validated['buyer_company_id']);
            $assessment = $this->buyerRiskService->assess($buyer, Auth::user(), $company->id);
        }

        $history = AIBuyerRisk::query()->where('company_id', $company->id)->latest()->paginate(10);
        $buyers = B2BCompany::query()->whereIn('company_type', B2BCompany::BUYER_TYPES)->orderBy('company_name')->limit(100)->get(['id', 'company_name']);

        return view('b2b.ai.buyer_risk', compact('company', 'assessment', 'history', 'buyers'));
    }

    public function freightRecommendation(Request $request)
    {
        $company = $this->requireAiAccess();
        $recommendation = null;

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'freight_quote_id' => ['nullable', 'integer'],
                'shipment_id' => ['nullable', 'integer'],
            ]);

            if (!empty($validated['freight_quote_id'])) {
                $quote = B2BFreightQuote::query()
                    ->where(function ($query) use ($company) {
                        $query->where('buyer_company_id', $company->id)
                            ->orWhere('supplier_company_id', $company->id);
                    })
                    ->findOrFail($validated['freight_quote_id']);
                $recommendation = $this->freightRecommendationService->recommendForQuote($quote, Auth::user(), $company->id);
            } elseif (!empty($validated['shipment_id'])) {
                $shipment = B2BShipment::query()
                    ->where(function ($query) use ($company) {
                        $query->where('buyer_company_id', $company->id)
                            ->orWhere('supplier_company_id', $company->id);
                    })
                    ->findOrFail($validated['shipment_id']);
                $recommendation = $this->freightRecommendationService->recommendForShipment($shipment, Auth::user(), $company->id);
            }
        }

        $history = AIFreightRecommendation::query()->where('company_id', $company->id)->latest()->paginate(10);
        $freightQuotes = B2BFreightQuote::query()
            ->where(function ($query) use ($company) {
                $query->where('buyer_company_id', $company->id)->orWhere('supplier_company_id', $company->id);
            })
            ->latest()
            ->limit(50)
            ->get(['id', 'quote_number']);
        $shipments = B2BShipment::query()
            ->where(function ($query) use ($company) {
                $query->where('buyer_company_id', $company->id)->orWhere('supplier_company_id', $company->id);
            })
            ->latest()
            ->limit(50)
            ->get(['id', 'shipment_number']);

        return view('b2b.ai.freight_recommendation', compact('company', 'recommendation', 'history', 'freightQuotes', 'shipments'));
    }

    public function currencyAnalysis(Request $request)
    {
        $company = $this->requireAiAccess();
        $analysis = null;

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'currency_code' => ['required', 'string', 'max:10'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'queue' => ['nullable', 'boolean'],
            ]);

            if ($request->boolean('queue')) {
                GenerateCurrencyAnalysisJob::dispatch($validated['currency_code'], (float) $validated['amount'], $company->id)
                    ->onConnection(config('ai.queue.connection', 'database'))
                    ->onQueue(config('ai.queue.queue', 'ai'));
                flash(translate('Currency analysis queued successfully.'))->success();
            } else {
                $analysis = $this->currencyAnalysisService->analyze($validated['currency_code'], (float) $validated['amount'], Auth::user(), $company->id);
            }
        }

        $history = AICurrencyAnalysis::query()->where('company_id', $company->id)->latest()->paginate(10);

        return view('b2b.ai.currency_analysis', compact('company', 'analysis', 'history'));
    }

    public function tradeFinanceRecommendation(Request $request)
    {
        $company = $this->requireAiAccess();
        $recommendation = null;

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'purchase_order_id' => ['nullable', 'integer'],
            ]);

            $purchaseOrder = B2BPurchaseOrder::query()
                ->where(function ($query) use ($company) {
                    $query->where('buyer_company_id', $company->id)
                        ->orWhere('supplier_company_id', $company->id);
                })
                ->findOrFail($validated['purchase_order_id']);

            $recommendation = $this->tradeFinanceRecommendationService->recommendForPurchaseOrder($purchaseOrder, Auth::user(), $company->id);
        }

        $history = AITradeFinanceRecommendation::query()->where('company_id', $company->id)->latest()->paginate(10);
        $purchaseOrders = B2BPurchaseOrder::query()
            ->where(function ($query) use ($company) {
                $query->where('buyer_company_id', $company->id)->orWhere('supplier_company_id', $company->id);
            })
            ->latest()
            ->limit(50)
            ->get(['id', 'po_number']);

        return view('b2b.ai.trade_finance', compact('company', 'recommendation', 'history', 'purchaseOrders'));
    }

    public function opportunities(Request $request)
    {
        $company = $this->requireAiAccess();

        if ($request->boolean('queue')) {
            GenerateOpportunityJob::dispatch($company->id)
                ->onConnection(config('ai.queue.connection', 'database'))
                ->onQueue(config('ai.queue.queue', 'ai'));
            flash(translate('Opportunity scan queued successfully.'))->success();
        } elseif ($request->boolean('refresh')) {
            $this->tradeOpportunityService->detectForCompany($company);
            flash(translate('Opportunity scan completed successfully.'))->success();
        }

        $opportunities = AITradeOpportunity::query()->where('company_id', $company->id)->latest()->paginate(12);

        return view('b2b.ai.opportunities', compact('company', 'opportunities'));
    }

    public function notifications(Request $request)
    {
        $company = $this->requireAiAccess();

        if ($request->boolean('refresh')) {
            $this->notificationService->generateForCompany($company);
            flash(translate('AI notifications generated successfully.'))->success();
        }

        $notifications = AINotificationEvent::query()->where('company_id', $company->id)->latest()->paginate(15);

        return view('b2b.ai.notifications', compact('company', 'notifications'));
    }

    public function dashboardInsights(Request $request)
    {
        $company = $this->requireAiAccess();

        if ($request->boolean('queue')) {
            GenerateDashboardInsightJob::dispatch($company->id)
                ->onConnection(config('ai.queue.connection', 'database'))
                ->onQueue(config('ai.queue.queue', 'ai'));
            flash(translate('Dashboard insight generation queued successfully.'))->success();
        } elseif ($request->boolean('refresh')) {
            $this->dashboardInsightService->generateForCompany($company, Auth::user());
            flash(translate('Dashboard insight generated successfully.'))->success();
        }

        $insights = AIDashboardInsight::query()->where('company_id', $company->id)->latest()->paginate(10);

        return view('b2b.ai.dashboard_insights', compact('company', 'insights'));
    }

    protected function activeCompany(): ?B2BCompany
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $accessibleCompanyIds = DB::table('b2b_companies')
            ->where('user_id', $user->id)
            ->pluck('id')
            ->merge(
                DB::table('b2b_company_members')
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->pluck('b2b_company_id')
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $activeCompanyId = (int) session('active_b2b_company_id');

        return B2BCompany::query()
            ->whereIn('id', $accessibleCompanyIds)
            ->when($activeCompanyId > 0, fn ($query) => $query->where('id', $activeCompanyId))
            ->first()
            ?: B2BCompany::query()->whereIn('id', $accessibleCompanyIds)->first();
    }

    protected function requiredActiveCompany(): B2BCompany
    {
        $company = $this->activeCompany();

        if (!$company) {
            abort(403, 'Active B2B company context is required.');
        }

        return $company;
    }

    protected function requireAiAccess(): B2BCompany
    {
        $company = $this->requiredActiveCompany();

        abort_unless($this->globalConfigService->aiEnabled(), 403, 'B2B AI tools are disabled in Global B2B Config.');

        return $company;
    }

    protected function requireAiToolAccess(string $field, string $toolName): B2BCompany
    {
        $company = $this->requiredActiveCompany();

        abort_unless(
            $this->globalConfigService->aiEnabled() && $this->globalConfigService->aiToolEnabled($field),
            403,
            $toolName . ' is disabled in Global B2B Config.'
        );

        return $company;
    }
}
