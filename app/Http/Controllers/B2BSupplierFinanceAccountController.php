<?php

namespace App\Http\Controllers;

use App\Models\B2BEscrow;
use App\Models\B2BProformaInvoice;
use App\Models\B2BSettlement;
use App\Services\B2BCompanyService;
use App\Services\B2BPermissionService;
use App\Services\B2BTradeFinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class B2BSupplierFinanceAccountController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BPermissionService $permissionService,
        protected B2BTradeFinanceService $tradeFinanceService
    ) {
    }

    public function earnings()
    {
        $company = $this->getSupplierCompany();

        $invoiceQuery = B2BProformaInvoice::query()
            ->where('supplier_company_id', $company->id);

        $totals = [
            'gross_revenue' => (float) (clone $invoiceQuery)->sum('grand_total'),
            'platform_fees' => (float) (clone $invoiceQuery)->sum('platform_fee_amount'),
            'net_earnings' => (float) (clone $invoiceQuery)->sum('supplier_payout_amount'),
            'released_earnings' => (float) (clone $invoiceQuery)->whereNotNull('supplier_paid_out_at')->sum('supplier_payout_amount'),
            'escrow_fees' => (float) (clone $invoiceQuery)->sum('escrow_fee_amount'),
        ];

        $invoices = (clone $invoiceQuery)
            ->with(['buyerCompany', 'purchaseOrder'])
            ->latest()
            ->paginate(12);

        return view('seller.b2b.finance.earnings', compact('company', 'totals', 'invoices'));
    }

    public function payouts()
    {
        $company = $this->getSupplierCompany();
        $canManageTradeFinance = $this->permissionService->canManageTradeFinance(Auth::id(), $company->id);

        $releasedEscrows = B2BEscrow::query()
            ->with(['reference', 'settlements'])
            ->where('supplier_company_id', $company->id)
            ->where('status', 'released')
            ->latest('released_at')
            ->paginate(10, ['*'], 'released_page');

        $settlements = B2BSettlement::query()
            ->with(['escrow.reference'])
            ->where('supplier_company_id', $company->id)
            ->latest()
            ->paginate(10, ['*'], 'settlements_page');

        $availableEscrowAmount = (float) B2BEscrow::query()
            ->where('supplier_company_id', $company->id)
            ->where('status', 'released')
            ->whereDoesntHave('settlements')
            ->sum('released_amount');

        $summary = [
            'available_payout' => $availableEscrowAmount,
            'requested_payouts' => (float) B2BSettlement::query()
                ->where('supplier_company_id', $company->id)
                ->whereIn('status', ['pending_approval', 'approved'])
                ->sum('net_amount'),
            'completed_payouts' => (float) B2BSettlement::query()
                ->where('supplier_company_id', $company->id)
                ->where('status', 'completed')
                ->sum('net_amount'),
            'payout_fees' => (float) B2BSettlement::query()
                ->where('supplier_company_id', $company->id)
                ->sum('fees'),
        ];

        return view('seller.b2b.finance.payouts', compact(
            'company',
            'releasedEscrows',
            'settlements',
            'summary',
            'canManageTradeFinance'
        ));
    }

    public function requestPayout(Request $request, int $escrowId)
    {
        $company = $this->getSupplierCompany();
        abort_unless($this->permissionService->canManageTradeFinance(Auth::id(), $company->id), 403);

        $escrow = B2BEscrow::query()
            ->where('supplier_company_id', $company->id)
            ->findOrFail($escrowId);

        if ($escrow->status !== 'released') {
            flash(translate('This payout is not ready yet.'))->warning();

            return back();
        }

        if ($escrow->settlements()->exists()) {
            flash(translate('A payout request already exists for this escrow.'))->warning();

            return back();
        }

        $validated = $request->validate([
            'settlement_method' => 'required|in:wallet,bank_transfer,wise,payoneer,manual',
            'fees' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string|max:255',
            'destination_details' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $this->tradeFinanceService->requestSettlement($escrow, [
            'settlement_method' => $validated['settlement_method'],
            'fees' => $validated['fees'] ?? 0,
            'reference' => $validated['reference'] ?? null,
            'destination_details' => ['summary' => $validated['destination_details'] ?? null],
            'notes' => $validated['notes'] ?? null,
        ], Auth::id(), $company->id);

        flash(translate('Payout request submitted successfully.'))->success();

        return back();
    }

    protected function getSupplierCompany()
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        abort_unless(
            $company &&
            $this->b2bCompanyService->isApprovedSupplier(Auth::id(), $company->id) &&
            $this->permissionService->canAccessCompany(Auth::id(), $company->id),
            403
        );

        return $company;
    }
}
