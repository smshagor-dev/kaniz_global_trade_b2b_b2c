<?php

namespace App\Http\Controllers;

use App\Models\B2BProformaInvoice;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BSampleOrder;
use App\Models\B2BShipment;
use App\Models\B2BTradeDocument;
use App\Services\B2BCompanyService;
use App\Services\B2BPermissionService;
use App\Services\B2BTradeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class B2BTradeDocumentController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BPermissionService $b2bPermissionService,
        protected B2BTradeService $b2bTradeService
    ) {
    }

    public function store(Request $request, string $type, int $id)
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());
        abort_unless($company && $this->b2bPermissionService->canAccessCompany(Auth::id(), $company->id), 403);

        $request->validate([
            'document_type' => 'required|in:' . implode(',', B2BTradeService::DOCUMENT_TYPES),
            'title' => 'nullable|string|max:255',
            'issued_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:issued_at',
            'notes' => 'nullable|string',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240',
        ]);

        $documentable = $this->resolveDocumentable($type, $id, $company->id);
        $this->b2bTradeService->storeTradeDocument($documentable, $request, Auth::id(), $company->id);

        flash(translate('Trade document uploaded successfully.'))->success();

        return back();
    }

    public function delete($id)
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());
        abort_unless($company && $this->b2bPermissionService->canAccessCompany(Auth::id(), $company->id), 403);

        $document = B2BTradeDocument::findOrFail($id);
        $this->authorizeDocumentCompany($document->documentable, $company->id);
        $this->b2bTradeService->deleteTradeDocument($document);

        flash(translate('Trade document deleted successfully.'))->success();

        return back();
    }

    protected function resolveDocumentable(string $type, int $id, int $companyId): Model
    {
        return match ($type) {
            'purchase-order' => tap(B2BPurchaseOrder::findOrFail($id), fn ($model) => $this->authorizeDocumentCompany($model, $companyId)),
            'proforma-invoice' => tap(B2BProformaInvoice::findOrFail($id), fn ($model) => $this->authorizeDocumentCompany($model, $companyId)),
            'shipment' => tap(B2BShipment::findOrFail($id), fn ($model) => $this->authorizeDocumentCompany($model, $companyId)),
            'sample-order' => tap(B2BSampleOrder::findOrFail($id), fn ($model) => $this->authorizeDocumentCompany($model, $companyId)),
            default => abort(404),
        };
    }

    protected function authorizeDocumentCompany(Model $documentable, int $companyId): void
    {
        $matches = collect([
            $documentable->buyer_company_id ?? null,
            $documentable->supplier_company_id ?? null,
            $documentable->company_id ?? null,
        ])->contains(fn ($value) => (int) $value === (int) $companyId);

        abort_unless($matches, 403);
    }
}
