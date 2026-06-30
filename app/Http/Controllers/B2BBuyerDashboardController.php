<?php

namespace App\Http\Controllers;

use App\Models\AIDashboardInsight;
use App\Models\AINotificationEvent;
use App\Models\AIPriceRecommendation;
use App\Models\AISupplierRisk;
use App\Models\B2BAuditLog;
use App\Models\B2BCompany;
use App\Models\B2BContainerShipment;
use App\Models\B2BFreightQuote;
use App\Models\B2BInsurancePolicy;
use App\Models\B2BProformaInvoice;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BQuotation;
use App\Models\B2BRfq;
use App\Models\B2BSampleOrder;
use App\Models\B2BShipment;
use App\Services\B2BCompanyService;
use App\Services\B2BDashboardService;
use Illuminate\Support\Facades\Auth;

class B2BBuyerDashboardController extends Controller
{
    public function __construct(
        protected B2BCompanyService $companyService,
        protected B2BDashboardService $dashboardService
    ) {
    }

    public function index()
    {
        return $this->renderDashboard();
    }

    public function __invoke()
    {
        return $this->renderDashboard();
    }

    protected function renderDashboard()
    {
        $company = $this->resolveCompany();

        if ($company && !$this->isAdminPreviewUser() && !$this->companyService->hasActiveBuyerPackage(Auth::id(), $company->id)) {
            flash(translate('An active buyer package is required to access the buyer dashboard.'))->warning();

            return redirect()->route('b2b.packages.index');
        }

        if (!$company) {
            if ($this->isAdminPreviewUser()) {
                flash(translate('No approved buyer company found for preview.'))->warning();

                return redirect()->route('admin.b2b.dashboard');
            }

            abort(403);
        }

        return view('b2b.dashboards.buyer', [
            'portal' => 'buyer',
            'company' => $company,
            'stats' => $this->dashboardService->buyerStats(Auth::id(), $company),
            'recent' => [
                'rfqs' => B2BRfq::where('b2b_company_id', $company->id)->latest()->limit(5)->get(),
                'quotations' => B2BQuotation::with(['rfq', 'supplierCompany'])
                    ->whereHas('rfq', fn ($query) => $query->where('b2b_company_id', $company->id))
                    ->latest()
                    ->limit(5)
                    ->get(),
                'purchaseOrders' => B2BPurchaseOrder::with(['supplierCompany', 'rfq'])->where('buyer_company_id', $company->id)->latest()->limit(5)->get(),
                'invoices' => B2BProformaInvoice::with(['supplierCompany'])->where('buyer_company_id', $company->id)->latest()->limit(5)->get(),
                'sampleOrders' => B2BSampleOrder::with(['supplierCompany'])->where('buyer_company_id', $company->id)->latest()->limit(5)->get(),
                'shipments' => B2BShipment::with(['supplierCompany'])->where('buyer_company_id', $company->id)->latest()->limit(5)->get(),
                'freightQuotes' => B2BFreightQuote::with(['supplierCompany'])->where('buyer_company_id', $company->id)->latest()->limit(5)->get(),
                'containers' => B2BContainerShipment::whereHas('freightQuote', fn ($query) => $query->where('buyer_company_id', $company->id))->latest()->limit(5)->get(),
                'insurancePolicies' => B2BInsurancePolicy::where('buyer_company_id', $company->id)->latest()->limit(5)->get(),
                'activity' => B2BAuditLog::where('actor_company_id', $company->id)->latest()->limit(8)->get(),
            ],
            'ai' => [
                'priceRecommendation' => AIPriceRecommendation::where('company_id', $company->id)->latest()->first(),
                'dashboardInsight' => AIDashboardInsight::where('company_id', $company->id)->latest()->first(),
                'notifications' => AINotificationEvent::where('company_id', $company->id)->latest()->limit(5)->get(),
            ],
        ]);
    }

    protected function resolveCompany(): ?B2BCompany
    {
        $company = $this->companyService->getCompanyByUser(Auth::id());

        if (
            $company &&
            $company->verification_status === 'approved' &&
            $company->isBuyerSide()
        ) {
            return $company;
        }

        if (!$this->isAdminPreviewUser()) {
            return null;
        }

        return B2BCompany::query()
            ->where('verification_status', 'approved')
            ->whereIn('company_type', B2BCompany::BUYER_TYPES)
            ->latest('id')
            ->first();
    }

    protected function isAdminPreviewUser(): bool
    {
        return in_array(Auth::user()?->user_type, ['admin', 'staff'], true);
    }
}
