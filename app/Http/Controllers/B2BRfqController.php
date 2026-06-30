<?php

namespace App\Http\Controllers;

use App\Jobs\RecalculateUserRiskJob;
use App\Models\B2BRfq;
use App\Models\Category;
use App\Models\Product;
use App\Services\B2BAuditService;
use App\Services\B2BCompanyService;
use App\Services\B2BNotificationService;
use App\Services\B2BPermissionService;
use App\Services\B2BTradeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class B2BRfqController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BNotificationService $b2bNotificationService,
        protected B2BAuditService $b2bAuditService,
        protected B2BPermissionService $b2bPermissionService
    )
    {
    }

    public function index()
    {
        $company = $this->getAccessibleBuyerCompany();
        $rfqs = B2BRfq::with(['company', 'product', 'quotations'])
            ->where('b2b_company_id', $company->id)
            ->latest()
            ->paginate(15);

        $canCreateRfq = $this->b2bCompanyService->canCreateRfq(Auth::id(), $company->id);

        return view('b2b.rfqs.index', compact('rfqs', 'canCreateRfq'));
    }

    public function create(Request $request)
    {
        $company = $this->getApprovedBuyerCompany();
        if (!$company) {
            return $this->buyerApprovalRedirect();
        }

        if (!$this->b2bCompanyService->canCreateRfq(Auth::id(), $company->id)) {
            return $this->buyerPermissionRedirect();
        }

        $selectedProduct = null;
        if ($request->filled('product_id')) {
            $selectedProduct = Product::where('id', $request->product_id)
                ->where('approved', 1)
                ->first();
        }

        $categories = Category::where('parent_id', 0)->orderBy('name')->get();
        $targetSupplierCompany = null;
        if ($request->filled('supplier_company_id')) {
            $targetSupplierCompany = \App\Models\B2BCompany::approvedSupplierSide()
                ->where('id', $request->supplier_company_id)
                ->first();
        }

        return view('b2b.rfqs.create', compact('company', 'categories', 'selectedProduct', 'targetSupplierCompany'));
    }

    public function store(Request $request)
    {
        $company = $this->getApprovedBuyerCompany();
        if (!$company) {
            return $this->buyerApprovalRedirect();
        }

        if (!$this->b2bCompanyService->canCreateRfq(Auth::id(), $company->id)) {
            return $this->buyerPermissionRedirect();
        }

        $data = $this->validatedData($request);
        $data['user_id'] = Auth::id();
        $data['b2b_company_id'] = $company->id;
        $data['status'] = 'open';
        $data['attachment'] = $this->storeFile($request, 'attachment', 'uploads/b2b_rfqs');

        if (!empty($data['product_id']) && empty($data['category_id'])) {
            $data['category_id'] = Product::find($data['product_id'])?->category_id;
        }

        $rfq = B2BRfq::create($data);
        $this->b2bAuditService->log(Auth::id(), $company->id, 'rfq_created', $rfq, 'RFQ created by buyer.', [
            'product_id' => $rfq->product_id,
            'quantity' => $rfq->quantity,
        ]);
        $this->b2bNotificationService->notifySuppliersAboutNewRfq($rfq);
        RecalculateUserRiskJob::dispatch(Auth::id(), 'rfq_created', 'Buyer created a new RFQ.');

        flash(translate('RFQ submitted successfully.'))->success();

        return redirect()->route('b2b.rfqs.index');
    }

    public function show($id)
    {
        $company = $this->getAccessibleBuyerCompany();
        $rfq = B2BRfq::with([
            'company',
            'product',
            'category',
            'quotations.supplier',
            'quotations.supplierCompany',
            'quotations.product',
            'quotations.negotiation',
        ])
            ->where('b2b_company_id', $company->id)
            ->findOrFail($id);

        $canCreateRfq = $this->b2bCompanyService->canCreateRfq(Auth::id(), $company->id);
        $canManagePurchaseOrder = $this->b2bPermissionService->canManagePurchaseOrder(Auth::id(), $company->id);

        return view('b2b.rfqs.show', compact('rfq', 'canCreateRfq', 'canManagePurchaseOrder'));
    }

    public function edit($id)
    {
        $company = $this->getApprovedBuyerCompany();
        if (!$company) {
            return $this->buyerApprovalRedirect();
        }

        if (!$this->b2bCompanyService->canCreateRfq(Auth::id(), $company->id)) {
            return $this->buyerPermissionRedirect();
        }

        $rfq = B2BRfq::where('b2b_company_id', $company->id)->findOrFail($id);

        if (in_array($rfq->status, ['closed', 'cancelled'])) {
            flash(translate('This RFQ can no longer be edited.'))->warning();
            return redirect()->route('b2b.rfqs.show', $rfq->id);
        }

        $categories = Category::where('parent_id', 0)->orderBy('name')->get();
        $selectedProduct = $rfq->product;

        return view('b2b.rfqs.edit', compact('rfq', 'company', 'categories', 'selectedProduct'));
    }

    public function update(Request $request, $id)
    {
        $company = $this->getApprovedBuyerCompany();
        if (!$company) {
            return $this->buyerApprovalRedirect();
        }

        if (!$this->b2bCompanyService->canCreateRfq(Auth::id(), $company->id)) {
            return $this->buyerPermissionRedirect();
        }

        $rfq = B2BRfq::where('b2b_company_id', $company->id)->findOrFail($id);

        if (in_array($rfq->status, ['closed', 'cancelled'])) {
            flash(translate('This RFQ can no longer be updated.'))->warning();
            return redirect()->route('b2b.rfqs.show', $rfq->id);
        }

        $data = $this->validatedData($request);
        $data['attachment'] = $this->storeFile($request, 'attachment', 'uploads/b2b_rfqs', $rfq->attachment);

        if (!empty($data['product_id']) && empty($data['category_id'])) {
            $data['category_id'] = Product::find($data['product_id'])?->category_id;
        }

        $rfq->update($data);
        $this->b2bAuditService->log(Auth::id(), $company->id, 'rfq_updated', $rfq, 'RFQ updated by buyer.', [
            'status' => $rfq->status,
        ]);
        RecalculateUserRiskJob::dispatch(Auth::id(), 'rfq_updated', 'Buyer updated an RFQ.');

        flash(translate('RFQ updated successfully.'))->success();

        return redirect()->route('b2b.rfqs.show', $rfq->id);
    }

    public function cancel($id)
    {
        $company = $this->getApprovedBuyerCompany();
        if (!$company) {
            return $this->buyerApprovalRedirect();
        }

        if (!$this->b2bCompanyService->canCreateRfq(Auth::id(), $company->id)) {
            return $this->buyerPermissionRedirect();
        }

        $rfq = B2BRfq::where('b2b_company_id', $company->id)->findOrFail($id);

        if (in_array($rfq->status, ['closed', 'cancelled'])) {
            flash(translate('This RFQ can no longer be cancelled.'))->warning();
            return back();
        }

        $rfq->update(['status' => 'cancelled']);
        $this->b2bAuditService->log(Auth::id(), $rfq->b2b_company_id, 'rfq_cancelled', $rfq, 'RFQ cancelled by buyer.', []);
        RecalculateUserRiskJob::dispatch(Auth::id(), 'rfq_cancelled', 'Buyer cancelled an RFQ.');

        flash(translate('RFQ cancelled successfully.'))->success();

        return back();
    }

    public function adminIndex(Request $request)
    {
        $rfqs = B2BRfq::with(['user', 'company', 'product'])
            ->withCount('quotations')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('company', function ($companyQuery) use ($search) {
                            $companyQuery->where('company_name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->appends($request->query());

        return view('backend.b2b.rfqs.index', compact('rfqs'));
    }

    public function adminShow($id)
    {
        $rfq = B2BRfq::with([
            'user',
            'company',
            'product',
            'category',
            'quotations.supplier',
            'quotations.supplierCompany',
            'quotations.product',
        ])->findOrFail($id);

        return view('backend.b2b.rfqs.show', compact('rfq'));
    }

    public function close($id)
    {
        $rfq = B2BRfq::findOrFail($id);

        if (!in_array($rfq->status, ['open', 'quoted'])) {
            flash(translate('Only open or quoted RFQs can be closed.'))->warning();
            return back();
        }

        $rfq->update(['status' => 'closed']);
        $this->b2bAuditService->log(Auth::id(), null, 'rfq_closed', $rfq, 'RFQ closed by admin.', []);

        flash(translate('RFQ closed successfully.'))->success();

        return back();
    }

    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'supplier_company_id' => [
                'nullable',
                Rule::exists('b2b_companies', 'id')->where(function ($query) {
                    $query->where('verification_status', 'approved')
                        ->whereIn('company_type', \App\Models\B2BCompany::SUPPLIER_TYPES);
                }),
            ],
            'category_id' => 'nullable|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'quantity' => 'required|numeric|min:1',
            'unit' => 'nullable|string|max:100',
            'target_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:20',
            'incoterm' => 'nullable|in:' . implode(',', B2BTradeService::INCOTERMS),
            'destination_country' => 'nullable|string|max:100',
            'destination_city' => 'nullable|string|max:100',
            'expected_delivery_date' => 'nullable|date',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'expires_at' => 'nullable|date|after:now',
        ]);
    }

    protected function getApprovedBuyerCompany()
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        if (
            !$company ||
            !$this->b2bCompanyService->isApprovedBuyer(Auth::id(), $company->id)
        ) {
            return null;
        }

        return $company;
    }

    protected function getAccessibleBuyerCompany()
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        if (
            !$company ||
            !$this->b2bCompanyService->isApprovedBuyer(Auth::id(), $company->id) ||
            !$this->b2bPermissionService->canAccessCompany(Auth::id(), $company->id)
        ) {
            abort(403);
        }

        return $company;
    }

    protected function buyerApprovalRedirect()
    {
        flash(translate('An approved buyer company, active package, and clean trust status are required to create RFQs.'))->error();
        return redirect()->route('b2b.packages.index');
    }

    protected function buyerPermissionRedirect()
    {
        flash(translate('You do not have permission to create or manage RFQs for this company, or your account is currently restricted.'))->warning();
        return redirect()->route('b2b.company.show');
    }

    protected function storeFile(Request $request, string $field, string $directoryPath, ?string $oldFile = null): ?string
    {
        if (!$request->hasFile($field)) {
            return $oldFile;
        }

        $directory = public_path($directoryPath);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if ($oldFile && File::exists(public_path($oldFile))) {
            File::delete(public_path($oldFile));
        }

        $file = $request->file($field);
        $fileName = time() . '_' . $field . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $fileName);

        return $directoryPath . '/' . $fileName;
    }
}
