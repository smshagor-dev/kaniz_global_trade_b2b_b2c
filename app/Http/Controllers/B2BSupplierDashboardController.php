<?php

namespace App\Http\Controllers;

use App\Models\B2BCompany;
use App\Models\AIDashboardInsight;
use App\Models\AINotificationEvent;
use App\Models\AISupplierRisk;
use App\Models\AITradeOpportunity;
use App\Models\B2BAuditLog;
use App\Models\B2BContainerShipment;
use App\Models\B2BFreightQuote;
use App\Models\B2BInsuranceClaim;
use App\Models\B2BInsurancePolicy;
use App\Models\B2BProformaInvoice;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BQuotation;
use App\Models\B2BRfq;
use App\Models\B2BSampleOrder;
use App\Models\B2BSettlement;
use App\Models\B2BShipment;
use App\Services\B2BCompanyService;
use App\Services\B2BDashboardService;
use Illuminate\Support\Facades\Auth;

class B2BSupplierDashboardController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BDashboardService $dashboardService
    ) {
    }

    public function index()
    {
        $company = $this->resolveSupplierCompany();

        if (!$company) {
            if ($this->isAdminPreviewUser()) {
                flash(translate('No approved supplier company found for preview.'))->warning();
                return redirect()->route('admin.b2b.dashboard');
            }

            abort(403);
        }

        $stats = $this->dashboardService->sellerStats(Auth::id(), $company);

        $recent = [
            'rfqs' => B2BRfq::whereIn('status', ['open', 'quoted'])->latest()->limit(5)->get(),
            'quotations' => B2BQuotation::with(['rfq'])->where('supplier_company_id', $company->id)->latest()->limit(5)->get(),
            'purchaseOrders' => B2BPurchaseOrder::with(['buyerCompany', 'rfq'])->where('supplier_company_id', $company->id)->latest()->limit(5)->get(),
            'invoices' => B2BProformaInvoice::with(['buyerCompany'])->where('supplier_company_id', $company->id)->latest()->limit(5)->get(),
            'sampleOrders' => B2BSampleOrder::with(['buyerCompany'])->where('supplier_company_id', $company->id)->latest()->limit(5)->get(),
            'shipments' => B2BShipment::with(['buyerCompany'])->where('supplier_company_id', $company->id)->latest()->limit(5)->get(),
            'freightQuotes' => B2BFreightQuote::with(['buyerCompany'])->where('supplier_company_id', $company->id)->latest()->limit(5)->get(),
            'containers' => B2BContainerShipment::whereHas('freightQuote', fn ($query) => $query->where('supplier_company_id', $company->id))->latest()->limit(5)->get(),
            'settlements' => B2BSettlement::where('supplier_company_id', $company->id)->latest()->limit(5)->get(),
            'insurancePolicies' => B2BInsurancePolicy::where('supplier_company_id', $company->id)->latest()->limit(5)->get(),
            'insuranceClaims' => B2BInsuranceClaim::where('supplier_company_id', $company->id)->latest()->limit(5)->get(),
            'activity' => B2BAuditLog::where('actor_company_id', $company->id)->latest()->limit(8)->get(),
        ];

        $ai = [
            'supplierRisk' => AISupplierRisk::where('company_id', $company->id)->latest()->first(),
            'opportunity' => AITradeOpportunity::where('company_id', $company->id)->latest()->first(),
            'dashboardInsight' => AIDashboardInsight::where('company_id', $company->id)->latest()->first(),
            'notifications' => AINotificationEvent::where('company_id', $company->id)->latest()->limit(5)->get(),
        ];

        return view('b2b.dashboards.supplier', compact('company', 'stats', 'recent', 'ai'));
    }

    protected function resolveSupplierCompany(): ?B2BCompany
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        if (
            $company &&
            $company->verification_status === 'approved' &&
            $company->isSupplierSide()
        ) {
            return $company;
        }

        if (!$this->isAdminPreviewUser()) {
            return null;
        }

        return B2BCompany::query()
            ->approvedSupplierSide()
            ->latest('id')
            ->first();
    }

    protected function isAdminPreviewUser(): bool
    {
        return in_array(Auth::user()?->user_type, ['admin', 'staff'], true);
    }
}
