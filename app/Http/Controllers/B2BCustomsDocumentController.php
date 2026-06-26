<?php

namespace App\Http\Controllers;

use App\Models\B2BContainerShipment;
use App\Models\B2BCustomsDocument;
use App\Models\B2BFreightQuote;
use App\Models\B2BProformaInvoice;
use App\Models\B2BPurchaseOrder;
use App\Services\B2BCompanyService;
use App\Services\B2BFreightService;
use App\Services\B2BPermissionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class B2BCustomsDocumentController extends Controller
{
    public function __construct(
        protected B2BCompanyService $companyService,
        protected B2BPermissionService $permissionService,
        protected B2BFreightService $freightService
    ) {
    }

    public function store(Request $request, string $type, int $id)
    {
        $company = $this->companyService->getCompanyByUser(Auth::id());
        abort_unless($company && $this->permissionService->canAccessCompany(Auth::id(), $company->id), 403);

        $request->validate([
            'document_type' => 'required|in:' . implode(',', B2BFreightService::CUSTOMS_DOCUMENT_TYPES),
            'title' => 'nullable|string|max:255',
            'issued_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:issued_at',
            'notes' => 'nullable|string',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240',
        ]);

        $documentable = $this->resolveDocumentable($type, $id, $company->id);
        $document = $this->freightService->storeCustomsDocument($documentable, $request, Auth::id(), $company->id);

        return response()->json(['success' => true, 'document_id' => $document->id]);
    }

    public function delete($id)
    {
        $company = $this->companyService->getCompanyByUser(Auth::id());
        abort_unless($company && $this->permissionService->canAccessCompany(Auth::id(), $company->id), 403);

        $document = B2BCustomsDocument::findOrFail($id);
        $this->freightService->deleteCustomsDocument($document);

        return response()->json(['success' => true]);
    }

    protected function resolveDocumentable(string $type, int $id, int $companyId): Model
    {
        $documentable = match ($type) {
            'freight-quote' => B2BFreightQuote::findOrFail($id),
            'container-shipment' => B2BContainerShipment::findOrFail($id),
            'purchase-order' => B2BPurchaseOrder::findOrFail($id),
            'proforma-invoice' => B2BProformaInvoice::findOrFail($id),
            default => abort(404),
        };

        $matches = collect([
            $documentable->buyer_company_id ?? null,
            $documentable->supplier_company_id ?? null,
            $documentable->company_id ?? null,
            $documentable->freightQuote->buyer_company_id ?? null,
            $documentable->freightQuote->supplier_company_id ?? null,
        ])->contains(fn ($value) => (int) $value === $companyId);

        abort_unless($matches, 403);

        return $documentable;
    }
}
