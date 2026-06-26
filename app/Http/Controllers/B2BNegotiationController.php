<?php

namespace App\Http\Controllers;

use App\Models\B2BNegotiation;
use App\Services\B2BAuditService;
use App\Services\B2BCompanyService;
use App\Services\B2BNotificationService;
use App\Services\B2BPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class B2BNegotiationController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BAuditService $b2bAuditService,
        protected B2BNotificationService $b2bNotificationService,
        protected B2BPermissionService $b2bPermissionService
    ) {
    }

    public function buyerIndex()
    {
        $company = $this->getBuyerCompany();
        $negotiations = B2BNegotiation::with(['supplierCompany', 'rfq', 'quotation', 'purchaseOrder', 'latestMessage.sender'])
            ->where('buyer_company_id', $company->id)
            ->latest('last_message_at')
            ->paginate(15);

        return view('b2b.negotiations.index', compact('negotiations'));
    }

    public function buyerShow($id)
    {
        $negotiation = $this->buyerQuery()->findOrFail($id);
        $this->markAsRead($negotiation, 'buyer');

        return view('b2b.negotiations.show', compact('negotiation'));
    }

    public function buyerStore(Request $request, $id)
    {
        $negotiation = $this->buyerQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->canParticipateInNegotiation(Auth::id(), $negotiation->buyer_company_id), 403);

        $this->storeMessage($request, $negotiation, 'buyer');

        flash(translate('Message sent successfully.'))->success();

        return back();
    }

    public function supplierIndex()
    {
        $company = $this->getSupplierCompany();
        $negotiations = B2BNegotiation::with(['buyerCompany', 'rfq', 'quotation', 'purchaseOrder', 'latestMessage.sender'])
            ->where('supplier_company_id', $company->id)
            ->latest('last_message_at')
            ->paginate(15);

        return view('seller.b2b.negotiations.index', compact('negotiations'));
    }

    public function supplierShow($id)
    {
        $negotiation = $this->supplierQuery()->findOrFail($id);
        $this->markAsRead($negotiation, 'supplier');

        return view('seller.b2b.negotiations.show', compact('negotiation'));
    }

    public function supplierStore(Request $request, $id)
    {
        $negotiation = $this->supplierQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->canParticipateInNegotiation(Auth::id(), $negotiation->supplier_company_id), 403);

        $this->storeMessage($request, $negotiation, 'supplier');

        flash(translate('Message sent successfully.'))->success();

        return back();
    }

    public function adminIndex()
    {
        $negotiations = B2BNegotiation::with(['buyerCompany', 'supplierCompany', 'rfq', 'quotation', 'purchaseOrder', 'latestMessage.sender'])
            ->latest('last_message_at')
            ->paginate(20);

        return view('backend.b2b.negotiations.index', compact('negotiations'));
    }

    public function adminShow($id)
    {
        $negotiation = B2BNegotiation::with(['buyerCompany', 'supplierCompany', 'rfq', 'quotation', 'purchaseOrder', 'messages.sender', 'messages.senderCompany'])
            ->findOrFail($id);

        return view('backend.b2b.negotiations.show', compact('negotiation'));
    }

    protected function buyerQuery()
    {
        $company = $this->getBuyerCompany();

        return B2BNegotiation::with(['buyerCompany', 'supplierCompany', 'rfq', 'quotation', 'purchaseOrder', 'messages.sender', 'messages.senderCompany'])
            ->where('buyer_company_id', $company->id);
    }

    protected function supplierQuery()
    {
        $company = $this->getSupplierCompany();

        return B2BNegotiation::with(['buyerCompany', 'supplierCompany', 'rfq', 'quotation', 'purchaseOrder', 'messages.sender', 'messages.senderCompany'])
            ->where('supplier_company_id', $company->id);
    }

    protected function storeMessage(Request $request, B2BNegotiation $negotiation, string $senderRole): void
    {
        $validated = $request->validate([
            'message' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:15360',
        ]);

        if (empty($validated['message']) && !$request->hasFile('attachment')) {
            abort(422, 'Message or attachment is required.');
        }

        $attachment = $this->storeFile($request, 'attachment', 'uploads/b2b_negotiations');
        $senderCompanyId = $this->b2bCompanyService->getCompanyByUser(Auth::id())?->id;

        $message = $negotiation->messages()->create([
            'sender_user_id' => Auth::id(),
            'sender_company_id' => $senderCompanyId,
            'sender_role' => $senderRole,
            'message_type' => $attachment ? 'attachment' : 'message',
            'message' => $validated['message'] ?? null,
            'attachment' => $attachment,
            'buyer_read_at' => $senderRole === 'buyer' ? now() : null,
            'supplier_read_at' => $senderRole === 'supplier' ? now() : null,
        ]);

        $negotiation->update(['last_message_at' => $message->created_at]);

        $this->b2bAuditService->log(
            Auth::id(),
            $senderCompanyId,
            'negotiation_message_sent',
            $negotiation,
            'Negotiation message sent.',
            ['message_id' => $message->id, 'message_type' => $message->message_type]
        );

        $this->b2bNotificationService->notifyNegotiationParticipants($negotiation, Auth::id());
    }

    protected function getBuyerCompany()
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        abort_unless(
            $company &&
            $this->b2bCompanyService->isApprovedBuyer(Auth::id(), $company->id) &&
            $this->b2bPermissionService->canAccessCompany(Auth::id(), $company->id),
            403
        );

        return $company;
    }

    protected function getSupplierCompany()
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        abort_unless(
            $company &&
            $this->b2bCompanyService->isApprovedSupplier(Auth::id(), $company->id) &&
            $this->b2bPermissionService->canAccessCompany(Auth::id(), $company->id),
            403
        );

        return $company;
    }

    protected function markAsRead(B2BNegotiation $negotiation, string $side): void
    {
        $column = $side === 'buyer' ? 'buyer_read_at' : 'supplier_read_at';
        $negotiation->messages()
            ->whereNull($column)
            ->where('sender_user_id', '!=', Auth::id())
            ->update([$column => now()]);
    }

    protected function storeFile(Request $request, string $field, string $directoryPath): ?string
    {
        if (!$request->hasFile($field)) {
            return null;
        }

        $directory = public_path($directoryPath);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $file = $request->file($field);
        $fileName = time() . '_' . $field . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $fileName);

        return $directoryPath . '/' . $fileName;
    }
}
