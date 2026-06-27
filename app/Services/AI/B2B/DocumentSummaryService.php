<?php

namespace App\Services\AI\B2B;

use App\Models\B2BProformaInvoice;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BRfq;
use App\Models\B2BShipment;
use App\Models\B2BTradeDocument;
use App\Models\User;
use App\Services\AI\AIManager;
use App\Services\AI\AIPromptService;
use App\Services\AI\AIRequestService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentSummaryService
{
    public function __construct(
        protected AIManager $manager,
        protected AIRequestService $requestService,
        protected AIPromptService $promptService
    ) {
    }

    public function summarize(string $type, int $id, User $user): array
    {
        $entity = $this->resolveEntity($type, $id, $user);
        $payload = $this->entityPayload($type, $entity);

        if (!$this->manager->defaultProvider()) {
            return [
                'title' => $payload['title'],
                'summary' => $payload['summary'],
                'action_items' => $payload['action_items'],
                'source' => 'deterministic',
            ];
        }

        $rendered = $this->promptService->render('b2b_document_summary', [
            'entity_type' => $type,
            'entity_json' => json_encode($payload),
        ]);

        $result = $this->requestService->request([
            'module' => 'b2b_document_summary',
            'system_prompt' => $rendered['system_prompt'],
            'prompt' => $rendered['user_prompt'],
            'user' => $user,
            'company_id' => $payload['company_id'] ?? null,
            'metadata' => [
                'entity_type' => $type,
                'entity_id' => $id,
            ],
        ]);

        $decoded = json_decode($result['content'], true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Document summary returned an invalid response.');
        }

        return array_merge($decoded, [
            'source' => 'ai',
            'provider' => $result['provider'],
        ]);
    }

    protected function resolveEntity(string $type, int $id, User $user): mixed
    {
        $companyIds = $this->accessibleCompanyIds($user);

        return match ($type) {
            'rfq' => B2BRfq::query()
                ->with(['company', 'category', 'product'])
                ->whereIn('b2b_company_id', $companyIds)
                ->findOrFail($id),
            'purchase-order' => B2BPurchaseOrder::query()
                ->with(['buyerCompany', 'supplierCompany', 'rfq'])
                ->where(function ($query) use ($companyIds) {
                    $query->whereIn('buyer_company_id', $companyIds)
                        ->orWhereIn('supplier_company_id', $companyIds);
                })
                ->findOrFail($id),
            'proforma-invoice' => B2BProformaInvoice::query()
                ->with(['buyerCompany', 'supplierCompany', 'purchaseOrder'])
                ->where(function ($query) use ($companyIds) {
                    $query->whereIn('buyer_company_id', $companyIds)
                        ->orWhereIn('supplier_company_id', $companyIds);
                })
                ->findOrFail($id),
            'shipment' => B2BShipment::query()
                ->with(['buyerCompany', 'supplierCompany', 'shippingProvider'])
                ->where(function ($query) use ($companyIds) {
                    $query->whereIn('buyer_company_id', $companyIds)
                        ->orWhereIn('supplier_company_id', $companyIds);
                })
                ->findOrFail($id),
            'trade-document' => B2BTradeDocument::query()
                ->with(['company', 'documentable'])
                ->whereIn('company_id', $companyIds)
                ->findOrFail($id),
            default => throw new RuntimeException('Unsupported document summary type.'),
        };
    }

    protected function entityPayload(string $type, mixed $entity): array
    {
        return match ($type) {
            'rfq' => [
                'company_id' => $entity->b2b_company_id,
                'title' => 'RFQ ' . $entity->title,
                'summary' => 'RFQ for ' . $entity->quantity . ' ' . $entity->unit . ' with status ' . $entity->status . '.',
                'action_items' => array_values(array_filter([
                    $entity->expires_at ? 'Review before ' . $entity->expires_at->format('Y-m-d H:i') : null,
                    $entity->incoterm ? 'Confirm incoterm ' . $entity->incoterm : null,
                    $entity->destination_country ? 'Validate delivery to ' . $entity->destination_country : null,
                ])),
                'data' => [
                    'title' => $entity->title,
                    'description' => $entity->description,
                    'category' => $entity->category?->getTranslation('name'),
                    'product' => $entity->product?->getTranslation('name'),
                    'quantity' => $entity->quantity,
                    'unit' => $entity->unit,
                    'target_price' => $entity->target_price,
                    'currency' => $entity->currency,
                    'incoterm' => $entity->incoterm,
                    'destination_country' => $entity->destination_country,
                    'status' => $entity->status,
                ],
            ],
            'purchase-order' => [
                'company_id' => $entity->buyer_company_id,
                'title' => 'Purchase Order ' . $entity->po_number,
                'summary' => 'Purchase order status is ' . $entity->status . ' for ' . $entity->total_amount . ' ' . $entity->currency . '.',
                'action_items' => array_values(array_filter([
                    $entity->delivery_deadline ? 'Track delivery deadline ' . $entity->delivery_deadline->format('Y-m-d') : null,
                    $entity->payment_terms ? 'Review payment terms' : null,
                ])),
                'data' => [
                    'po_number' => $entity->po_number,
                    'status' => $entity->status,
                    'currency' => $entity->currency,
                    'total_amount' => $entity->total_amount,
                    'payment_terms' => $entity->payment_terms,
                    'shipping_terms' => $entity->shipping_terms,
                    'incoterms' => $entity->incoterms,
                    'delivery_deadline' => optional($entity->delivery_deadline)->format('Y-m-d'),
                ],
            ],
            'proforma-invoice' => [
                'company_id' => $entity->buyer_company_id,
                'title' => 'Proforma Invoice ' . ($entity->invoice_number ?? ('#' . $entity->id)),
                'summary' => 'Proforma invoice status is ' . $entity->status . '.',
                'action_items' => ['Check invoice amount, payment milestone, and acceptance status.'],
                'data' => $entity->toArray(),
            ],
            'shipment' => [
                'company_id' => $entity->buyer_company_id,
                'title' => 'Shipment ' . $entity->shipment_number,
                'summary' => 'Shipment status is ' . $entity->status . ' with tracking ' . ($entity->tracking_number ?: 'pending') . '.',
                'action_items' => array_values(array_filter([
                    $entity->estimated_arrival ? 'Monitor ETA ' . $entity->estimated_arrival->format('Y-m-d') : null,
                    $entity->tracking_number ? 'Confirm carrier tracking events' : null,
                ])),
                'data' => [
                    'shipment_number' => $entity->shipment_number,
                    'status' => $entity->status,
                    'tracking_number' => $entity->tracking_number,
                    'transport_mode' => $entity->transport_mode,
                    'origin_country' => $entity->origin_country,
                    'destination_country' => $entity->destination_country,
                    'estimated_arrival' => optional($entity->estimated_arrival)->format('Y-m-d'),
                    'carrier_status' => $entity->carrier_status,
                ],
            ],
            'trade-document' => [
                'company_id' => $entity->company_id,
                'title' => 'Trade Document ' . $entity->title,
                'summary' => 'Document type ' . $entity->document_type . '.',
                'action_items' => array_values(array_filter([
                    $entity->expires_at ? 'Review expiry date ' . $entity->expires_at->format('Y-m-d') : null,
                    $entity->documentable_type ? 'Check linked workflow record' : null,
                ])),
                'data' => [
                    'title' => $entity->title,
                    'document_type' => $entity->document_type,
                    'issued_at' => optional($entity->issued_at)->format('Y-m-d'),
                    'expires_at' => optional($entity->expires_at)->format('Y-m-d'),
                    'notes' => $entity->notes,
                ],
            ],
        };
    }

    protected function accessibleCompanyIds(User $user): array
    {
        $owned = DB::table('b2b_companies')->where('user_id', $user->id)->pluck('id');
        $member = DB::table('b2b_company_members')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('b2b_company_id');

        return $owned->merge($member)->map(fn ($id) => (int) $id)->unique()->values()->all();
    }
}
