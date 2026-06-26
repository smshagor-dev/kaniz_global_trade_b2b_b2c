<?php

namespace App\Services;

use App\Models\B2BQuotation;
use App\Models\B2BRfq;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BProformaInvoice;
use App\Models\B2BNegotiation;
use App\Models\B2BContainerShipment;
use App\Models\B2BFreightQuote;
use App\Models\B2BShipment;
use App\Models\NotificationType;
use App\Models\User;
use App\Notifications\CustomNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

class B2BNotificationService
{
    public function notifySuppliersAboutNewRfq(B2BRfq $rfq): void
    {
        if ($rfq->supplier_company_id) {
            $targetSupplier = User::query()
                ->whereHas('b2bCompany', function ($query) use ($rfq) {
                    $query->where('id', $rfq->supplier_company_id)
                        ->where('verification_status', 'approved')
                        ->whereIn('company_type', ['supplier', 'manufacturer', 'wholesaler', 'distributor']);
                })
                ->first();

            if ($targetSupplier) {
                $this->sendLinkNotification(collect([$targetSupplier]), route('seller.b2b.rfqs.index'));
            }

            return;
        }

        $supplierIds = User::query()
            ->where('user_type', 'seller')
            ->where('id', '!=', $rfq->user_id)
            ->whereHas('b2bCompany', function ($query) {
                $query->where('verification_status', 'approved')
                    ->whereIn('company_type', ['supplier', 'manufacturer', 'wholesaler', 'distributor']);
            })
            ->pluck('id');

        if ($supplierIds->isEmpty()) {
            return;
        }

        $this->sendLinkNotification(
            User::whereIn('id', $supplierIds)->get(),
            route('seller.b2b.rfqs.index')
        );
    }

    public function notifyBuyerAboutNewQuotation(B2BQuotation $quotation): void
    {
        if (!$quotation->relationLoaded('rfq')) {
            $quotation->load('rfq');
        }

        $buyer = User::find($quotation->rfq?->user_id);
        if (!$buyer) {
            return;
        }

        $this->sendLinkNotification(collect([$buyer]), route('b2b.rfqs.show', $quotation->rfq_id));
    }

    public function notifyQuotationDecision(B2BQuotation $acceptedQuotation, Collection $rejectedSupplierIds): void
    {
        $acceptedSupplier = User::find($acceptedQuotation->supplier_user_id);
        if ($acceptedSupplier) {
            $this->sendLinkNotification(
                collect([$acceptedSupplier]),
                route('seller.b2b.quotations.show', $acceptedQuotation->id)
            );
        }

        if ($rejectedSupplierIds->isNotEmpty()) {
            $rejectedSuppliers = User::whereIn('id', $rejectedSupplierIds->unique()->values())->get();
            $this->sendLinkNotification($rejectedSuppliers, route('seller.b2b.quotations.index'));
        }
    }

    public function notifyBuyerAboutWithdrawnQuotation(B2BQuotation $quotation): void
    {
        if (!$quotation->relationLoaded('rfq')) {
            $quotation->load('rfq');
        }

        $buyer = User::find($quotation->rfq?->user_id);
        if (!$buyer) {
            return;
        }

        $this->sendLinkNotification(collect([$buyer]), route('b2b.rfqs.show', $quotation->rfq_id));
    }

    public function notifySupplierAboutPurchaseOrder(B2BPurchaseOrder $purchaseOrder): void
    {
        $supplier = User::find($purchaseOrder->supplier_user_id);
        if (!$supplier) {
            return;
        }

        $this->sendLinkNotification(collect([$supplier]), route('seller.b2b.purchase-orders.show', $purchaseOrder->id));
    }

    public function notifyBuyerAboutPurchaseOrderDecision(B2BPurchaseOrder $purchaseOrder): void
    {
        $buyer = User::find($purchaseOrder->buyer_user_id);
        if (!$buyer) {
            return;
        }

        $this->sendLinkNotification(collect([$buyer]), route('b2b.purchase-orders.show', $purchaseOrder->id));
    }

    public function notifyBuyerAboutProformaInvoice(B2BProformaInvoice $invoice): void
    {
        $buyer = User::find($invoice->buyer_user_id);
        if (!$buyer) {
            return;
        }

        $this->sendLinkNotification(collect([$buyer]), route('b2b.proforma-invoices.show', $invoice->id));
    }

    public function notifySupplierAboutInvoiceDecision(B2BProformaInvoice $invoice): void
    {
        $supplier = User::find($invoice->supplier_user_id);
        if (!$supplier) {
            return;
        }

        $this->sendLinkNotification(collect([$supplier]), route('seller.b2b.proforma-invoices.show', $invoice->id));
    }

    public function notifyNegotiationParticipants(B2BNegotiation $negotiation, int $senderUserId): void
    {
        if ($negotiation->buyer_user_id && (int) $negotiation->buyer_user_id !== $senderUserId) {
            $buyer = User::find($negotiation->buyer_user_id);
            if ($buyer) {
                $this->sendLinkNotification(collect([$buyer]), route('b2b.negotiations.show', $negotiation->id));
            }
        }

        if ($negotiation->supplier_user_id && (int) $negotiation->supplier_user_id !== $senderUserId) {
            $supplier = User::find($negotiation->supplier_user_id);
            if ($supplier) {
                $this->sendLinkNotification(collect([$supplier]), route('seller.b2b.negotiations.show', $negotiation->id));
            }
        }
    }

    public function notifyShipmentTrackingUpdate(B2BShipment $shipment, string $status): void
    {
        if (!$shipment->relationLoaded('purchaseOrder')) {
            $shipment->load(['purchaseOrder', 'sampleOrder', 'buyerCompany', 'supplierCompany']);
        }

        $buyerLink = route('b2b.shipments.show', $shipment->id);
        $supplierLink = route('seller.b2b.shipments.show', $shipment->id);

        $buyerUser = User::find(
            $shipment->purchaseOrder?->buyer_user_id
            ?? $shipment->sampleOrder?->buyer_user_id
            ?? $shipment->buyerCompany?->user_id
        );

        $supplierUser = User::find(
            $shipment->purchaseOrder?->supplier_user_id
            ?? $shipment->sampleOrder?->supplier_user_id
            ?? $shipment->supplierCompany?->user_id
        );

        if ($buyerUser) {
            $this->sendLinkNotification(collect([$buyerUser]), $buyerLink);
        }

        if ($supplierUser && (!$buyerUser || $supplierUser->id !== $buyerUser->id)) {
            $this->sendLinkNotification(collect([$supplierUser]), $supplierLink);
        }
    }

    public function notifyFreightQuoteSubmitted(B2BFreightQuote $quote): void
    {
        $this->notifyCompanies(
            $quote->buyer_company_id,
            $quote->supplier_company_id,
            route('b2b.freight-quotes.select', $quote->id)
        );
    }

    public function notifyContainerBooked(B2BContainerShipment $shipment): void
    {
        $shipment->loadMissing('freightQuote');

        $this->notifyCompanies(
            $shipment->freightQuote?->buyer_company_id,
            $shipment->freightQuote?->supplier_company_id,
            route('b2b.container-tracking.track', ['container_number' => $shipment->container_number])
        );
    }

    public function notifyContainerStatusUpdate(B2BContainerShipment $shipment, string $status): void
    {
        $shipment->loadMissing('freightQuote');

        $this->notifyCompanies(
            $shipment->freightQuote?->buyer_company_id,
            $shipment->freightQuote?->supplier_company_id,
            route('b2b.container-tracking.track', [
                'container_number' => $shipment->container_number,
                'bill_of_lading_number' => $shipment->bill_of_lading_number,
                'booking_number' => $shipment->booking_number,
            ])
        );
    }

    protected function notifyCompanies(?int $buyerCompanyId, ?int $supplierCompanyId, string $link): void
    {
        $buyerUser = $buyerCompanyId ? User::whereHas('b2bCompany', fn ($query) => $query->where('id', $buyerCompanyId))->first() : null;
        $supplierUser = $supplierCompanyId ? User::whereHas('b2bCompany', fn ($query) => $query->where('id', $supplierCompanyId))->first() : null;

        if ($buyerUser) {
            $this->sendLinkNotification(collect([$buyerUser]), $link);
        }

        if ($supplierUser && (!$buyerUser || $supplierUser->id !== $buyerUser->id)) {
            $this->sendLinkNotification(collect([$supplierUser]), $link);
        }
    }

    protected function sendLinkNotification(EloquentCollection|Collection $users, string $link): void
    {
        if ($users->isEmpty() || !$this->supportsDatabaseNotifications()) {
            return;
        }

        $notificationType = NotificationType::query()
            ->where('type', 'custom')
            ->where('status', 1)
            ->first();

        if (!$notificationType) {
            return;
        }

        Notification::send($users, new CustomNotification([
            'link' => $link,
            'notification_type_id' => $notificationType->id,
        ]));
    }

    protected function supportsDatabaseNotifications(): bool
    {
        return Schema::hasTable('notifications') && Schema::hasTable('notification_types');
    }
}
