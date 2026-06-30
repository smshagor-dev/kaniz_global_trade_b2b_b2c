<?php

namespace App\Http\Controllers;

use App\Models\B2BNegotiation;
use App\Models\B2BNegotiationMessage;
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
        abort_unless($this->b2bPermissionService->canParticipateInNegotiation(Auth::id(), $company->id), 403);

        return view('b2b.negotiations.index');
    }

    public function buyerShow($id)
    {
        $negotiation = $this->buyerQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->canParticipateInNegotiation(Auth::id(), $negotiation->buyer_company_id), 403);
        $this->markAsRead($negotiation, 'buyer');

        return view('b2b.negotiations.show', compact('negotiation'));
    }

    public function buyerListData()
    {
        $company = $this->getBuyerCompany();
        abort_unless($this->b2bPermissionService->canParticipateInNegotiation(Auth::id(), $company->id), 403);

        $negotiations = $this->buyerListQuery()->get();

        return response()->json([
            'data' => $negotiations->map(fn (B2BNegotiation $negotiation) => $this->serializeListItem($negotiation, 'buyer'))->values(),
        ]);
    }

    public function buyerShowData($id)
    {
        $negotiation = $this->buyerQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->canParticipateInNegotiation(Auth::id(), $negotiation->buyer_company_id), 403);
        $this->markAsRead($negotiation, 'buyer');
        $negotiation->load(['messages.sender', 'messages.senderCompany']);

        return response()->json([
            'data' => $this->serializeThread($negotiation, 'buyer'),
        ]);
    }

    public function buyerStore(Request $request, $id)
    {
        $negotiation = $this->buyerQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->canParticipateInNegotiation(Auth::id(), $negotiation->buyer_company_id), 403);

        $message = $this->storeMessage($request, $negotiation, 'buyer');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => translate('Message sent successfully.'),
                'data' => $this->serializeMessage($message),
            ]);
        }

        flash(translate('Message sent successfully.'))->success();

        return back();
    }

    public function supplierIndex()
    {
        $company = $this->getSupplierCompany();
        abort_unless($this->b2bPermissionService->canParticipateInNegotiation(Auth::id(), $company->id), 403);

        return view('seller.b2b.negotiations.index');
    }

    public function supplierShow($id)
    {
        $negotiation = $this->supplierQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->canParticipateInNegotiation(Auth::id(), $negotiation->supplier_company_id), 403);
        $this->markAsRead($negotiation, 'supplier');

        return view('seller.b2b.negotiations.show', compact('negotiation'));
    }

    public function supplierListData()
    {
        $company = $this->getSupplierCompany();
        abort_unless($this->b2bPermissionService->canParticipateInNegotiation(Auth::id(), $company->id), 403);

        $negotiations = $this->supplierListQuery()->get();

        return response()->json([
            'data' => $negotiations->map(fn (B2BNegotiation $negotiation) => $this->serializeListItem($negotiation, 'supplier'))->values(),
        ]);
    }

    public function supplierShowData($id)
    {
        $negotiation = $this->supplierQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->canParticipateInNegotiation(Auth::id(), $negotiation->supplier_company_id), 403);
        $this->markAsRead($negotiation, 'supplier');
        $negotiation->load(['messages.sender', 'messages.senderCompany']);

        return response()->json([
            'data' => $this->serializeThread($negotiation, 'supplier'),
        ]);
    }

    public function supplierStore(Request $request, $id)
    {
        $negotiation = $this->supplierQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->canParticipateInNegotiation(Auth::id(), $negotiation->supplier_company_id), 403);

        $message = $this->storeMessage($request, $negotiation, 'supplier');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => translate('Message sent successfully.'),
                'data' => $this->serializeMessage($message),
            ]);
        }

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

        return B2BNegotiation::with(['buyerCompany', 'supplierCompany', 'rfq', 'quotation', 'purchaseOrder'])
            ->where('buyer_company_id', $company->id);
    }

    protected function supplierQuery()
    {
        $company = $this->getSupplierCompany();

        return B2BNegotiation::with(['buyerCompany', 'supplierCompany', 'rfq', 'quotation', 'purchaseOrder'])
            ->where('supplier_company_id', $company->id);
    }

    protected function buyerListQuery()
    {
        $company = $this->getBuyerCompany();

        return B2BNegotiation::with(['supplierCompany', 'rfq', 'quotation', 'purchaseOrder', 'latestMessage.sender'])
            ->withCount(['messages as unread_messages_count' => function ($query) {
                $query->whereNull('buyer_read_at')
                    ->where('sender_user_id', '!=', Auth::id());
            }])
            ->where('buyer_company_id', $company->id)
            ->orderByRaw('COALESCE(last_message_at, created_at) desc');
    }

    protected function supplierListQuery()
    {
        $company = $this->getSupplierCompany();

        return B2BNegotiation::with(['buyerCompany', 'rfq', 'quotation', 'purchaseOrder', 'latestMessage.sender'])
            ->withCount(['messages as unread_messages_count' => function ($query) {
                $query->whereNull('supplier_read_at')
                    ->where('sender_user_id', '!=', Auth::id());
            }])
            ->where('supplier_company_id', $company->id)
            ->orderByRaw('COALESCE(last_message_at, created_at) desc');
    }

    protected function storeMessage(Request $request, B2BNegotiation $negotiation, string $senderRole): B2BNegotiationMessage
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

        return $message->load(['sender', 'senderCompany']);
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

    protected function serializeListItem(B2BNegotiation $negotiation, string $side): array
    {
        $counterparty = $side === 'buyer' ? $negotiation->supplierCompany : $negotiation->buyerCompany;
        $latestMessage = $negotiation->latestMessage;
        $title = $negotiation->rfq?->title ?: ($negotiation->purchaseOrder?->po_number ?: translate('Untitled conversation'));

        return [
            'id' => $negotiation->id,
            'title' => $title,
            'subtitle' => $counterparty?->company_name ?: translate('Unknown company'),
            'status' => $negotiation->quotation?->status ? ucfirst((string) $negotiation->quotation->status) : null,
            'latest_message' => $latestMessage?->message ?: ($latestMessage?->attachment ? translate('Attachment shared') : translate('No messages yet')),
            'latest_message_at' => optional($negotiation->last_message_at ?: $latestMessage?->created_at)?->format('d M Y, h:i A'),
            'latest_message_human' => optional($negotiation->last_message_at ?: $latestMessage?->created_at)?->diffForHumans(),
            'unread_messages_count' => (int) ($negotiation->unread_messages_count ?? 0),
            'url' => $side === 'buyer'
                ? route('b2b.negotiations.show', $negotiation->id)
                : route('seller.b2b.negotiations.show', $negotiation->id),
        ];
    }

    protected function serializeThread(B2BNegotiation $negotiation, string $side): array
    {
        $counterparty = $side === 'buyer' ? $negotiation->supplierCompany : $negotiation->buyerCompany;
        $title = $negotiation->rfq?->title ?: ($negotiation->purchaseOrder?->po_number ?: translate('Untitled conversation'));

        return [
            'id' => $negotiation->id,
            'title' => $title,
            'subtitle' => $counterparty?->company_name ?: translate('Unknown company'),
            'status' => $negotiation->quotation?->status ? ucfirst((string) $negotiation->quotation->status) : null,
            'reference' => [
                'rfq' => $negotiation->rfq?->title,
                'quotation' => $negotiation->quotation ? ('#' . $negotiation->quotation->id) : null,
                'purchase_order' => $negotiation->purchaseOrder?->po_number,
            ],
            'company_profile' => $counterparty ? [
                'name' => $counterparty->company_name,
                'company_type' => $counterparty->company_type ? ucfirst((string) $counterparty->company_type) : null,
                'verification_status' => $counterparty->verification_status ? ucfirst((string) $counterparty->verification_status) : null,
                'verified_supplier_badge' => (bool) $counterparty->verified_supplier_badge,
                'premium_verified' => (bool) $counterparty->premium_verified,
                'featured_supplier' => (bool) $counterparty->featured_supplier,
                'location' => collect([$counterparty->city, $counterparty->country])->filter()->implode(', '),
                'website' => $counterparty->website,
                'business_email' => $counterparty->business_email,
                'phone' => $counterparty->phone,
                'public_profile_url' => $counterparty->isSupplierSide() && $counterparty->public_profile_enabled && $counterparty->public_slug
                    ? route('b2b.suppliers.show', $counterparty->public_slug)
                    : null,
                'documents' => collect([
                    [
                        'key' => 'bank_check',
                        'label' => translate('Bank Check'),
                        'url' => $counterparty->bank_check_file ? asset($counterparty->bank_check_file) : null,
                        'name' => $counterparty->bank_check_file ? basename($counterparty->bank_check_file) : null,
                    ],
                    [
                        'key' => 'trade_license',
                        'label' => translate('Trade License'),
                        'url' => $counterparty->trade_license_file ? asset($counterparty->trade_license_file) : null,
                        'name' => $counterparty->trade_license_file ? basename($counterparty->trade_license_file) : null,
                    ],
                    [
                        'key' => 'tax_document',
                        'label' => translate('Tax Document'),
                        'url' => $counterparty->tax_document_file ? asset($counterparty->tax_document_file) : null,
                        'name' => $counterparty->tax_document_file ? basename($counterparty->tax_document_file) : null,
                    ],
                ])->filter(fn (array $document) => !empty($document['url']))->values()->all(),
            ] : null,
            'messages' => $negotiation->messages->sortBy('created_at')->values()->map(fn (B2BNegotiationMessage $message) => $this->serializeMessage($message))->all(),
        ];
    }

    protected function serializeMessage(B2BNegotiationMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_name' => $message->sender?->name ?: ucfirst((string) $message->sender_role),
            'sender_company' => $message->senderCompany?->company_name,
            'sender_role' => $message->sender_role,
            'is_mine' => (int) $message->sender_user_id === (int) Auth::id(),
            'message' => $message->message,
            'message_type' => $message->message_type,
            'attachment' => $message->attachment ? asset($message->attachment) : null,
            'attachment_name' => $message->attachment ? basename($message->attachment) : null,
            'created_at' => optional($message->created_at)?->format('d M Y, h:i A'),
            'created_at_human' => optional($message->created_at)?->diffForHumans(),
        ];
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
