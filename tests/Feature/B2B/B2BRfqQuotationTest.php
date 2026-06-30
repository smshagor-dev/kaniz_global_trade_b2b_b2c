<?php

namespace Tests\Feature\B2B;

use App\Models\B2BNegotiation;
use App\Models\B2BPurchaseOrder;

class B2BRfqQuotationTest extends B2BFeatureTestCase
{
    public function test_procurement_manager_can_create_rfq(): void
    {
        $buyerOwner = $this->createUser();
        $buyerCompany = $this->createCompany($buyerOwner, ['company_type' => 'buyer']);

        $procurementUser = $this->createUser();
        $this->createCompanyMember($buyerCompany, $procurementUser, 'procurement_manager');

        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, [
            'company_type' => 'supplier',
            'verification_status' => 'approved',
        ]);

        $this->setActiveCompany($buyerCompany);

        $this->actingAs($procurementUser)->post(route('b2b.rfqs.store'), [
            'supplier_company_id' => $supplierCompany->id,
            'title' => 'Need stainless fasteners',
            'description' => 'Bulk fastener RFQ',
            'quantity' => 500,
            'unit' => 'pcs',
            'target_price' => 5.5,
            'currency' => 'USD',
            'incoterm' => 'FOB',
            'destination_country' => 'Bangladesh',
            'destination_city' => 'Dhaka',
            'expires_at' => now()->addDays(5)->toDateTimeString(),
        ])->assertRedirect(route('b2b.rfqs.index'));

        $this->assertDatabaseHas('b2b_rfqs', [
            'b2b_company_id' => $buyerCompany->id,
            'user_id' => $procurementUser->id,
            'supplier_company_id' => $supplierCompany->id,
            'title' => 'Need stainless fasteners',
            'incoterm' => 'FOB',
            'status' => 'open',
        ]);
    }

    public function test_supplier_can_submit_quotation_for_open_rfq(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $rfq = $this->createRfq($buyerCompany, $buyerUser);

        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, [
            'company_type' => 'supplier',
        ]);
        $this->createCompanyMember($supplierCompany, $supplierUser, 'sales_manager');

        $this->setActiveCompany($supplierCompany);

        $this->actingAs($supplierUser)->post(route('seller.b2b.rfqs.quote.store', $rfq->id), [
            'price' => 18.75,
            'currency' => 'USD',
            'moq' => 25,
            'lead_time_days' => 12,
            'shipping_terms' => 'Sea',
            'incoterm' => 'FOB',
            'payment_terms' => '50% advance',
            'message' => 'Quotation from supplier',
        ])->assertRedirect(route('seller.b2b.quotations.index'));

        $this->assertDatabaseHas('b2b_quotations', [
            'rfq_id' => $rfq->id,
            'supplier_user_id' => $supplierUser->id,
            'supplier_company_id' => $supplierCompany->id,
            'price' => 18.75,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('b2b_rfqs', [
            'id' => $rfq->id,
            'status' => 'quoted',
        ]);
    }

    public function test_supplier_can_see_submit_quote_action_for_related_quoted_rfq(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $rfq = $this->createRfq($buyerCompany, $buyerUser, [
            'status' => 'quoted',
        ]);

        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, [
            'company_type' => 'supplier',
        ]);

        $this->setActiveCompany($supplierCompany);

        $this->actingAs($supplierUser)
            ->get(route('seller.b2b.rfqs.index'))
            ->assertOk()
            ->assertSee('Submit Quote');
    }

    public function test_supplier_can_access_quote_page_after_submitting_but_cannot_submit_duplicate_quote(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $rfq = $this->createRfq($buyerCompany, $buyerUser);

        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, [
            'company_type' => 'supplier',
        ]);

        $quotation = $this->createQuotation($rfq, $supplierCompany, $supplierUser);

        $this->setActiveCompany($supplierCompany);

        $this->actingAs($supplierUser)
            ->get(route('seller.b2b.rfqs.quote', $rfq->id))
            ->assertOk()
            ->assertSee('already submitted a quotation')
            ->assertSee((string) $quotation->price)
            ->assertDontSee('type="submit"', false);

        $this->actingAs($supplierUser)->post(route('seller.b2b.rfqs.quote.store', $rfq->id), [
            'price' => 22.50,
            'currency' => 'USD',
            'moq' => 40,
        ])->assertRedirect(route('seller.b2b.quotations.edit', $quotation->id));
    }

    public function test_quote_form_shows_rfq_title_when_buyer_did_not_select_product(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $rfq = $this->createRfq($buyerCompany, $buyerUser, [
            'product_id' => null,
            'title' => 'Custom Copper Wire Request',
        ]);

        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, [
            'company_type' => 'supplier',
        ]);

        $this->setActiveCompany($supplierCompany);

        $this->actingAs($supplierUser)
            ->get(route('seller.b2b.rfqs.quote', $rfq->id))
            ->assertOk()
            ->assertSee('Custom Copper Wire Request');
    }

    public function test_supplier_cannot_change_currency_from_buyer_rfq_currency(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $rfq = $this->createRfq($buyerCompany, $buyerUser, [
            'currency' => 'EUR',
        ]);

        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, [
            'company_type' => 'supplier',
        ]);
        $this->createCompanyMember($supplierCompany, $supplierUser, 'sales_manager');

        $this->setActiveCompany($supplierCompany);

        $this->actingAs($supplierUser)->post(route('seller.b2b.rfqs.quote.store', $rfq->id), [
            'price' => 18.75,
            'currency' => 'USD',
            'moq' => 25,
            'lead_time_days' => 12,
            'shipping_terms' => 'Sea',
            'incoterm' => 'FOB',
            'payment_terms' => '50% advance',
            'message' => 'Quotation from supplier',
        ])->assertRedirect(route('seller.b2b.quotations.index'));

        $this->assertDatabaseHas('b2b_quotations', [
            'rfq_id' => $rfq->id,
            'supplier_company_id' => $supplierCompany->id,
            'currency' => 'EUR',
        ]);
    }

    public function test_accepting_quotation_generates_purchase_order_and_rejects_others(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $rfq = $this->createRfq($buyerCompany, $buyerUser);

        $supplierOneUser = $this->createSellerUser();
        $supplierOneCompany = $this->createCompany($supplierOneUser, ['company_type' => 'supplier']);
        $supplierTwoUser = $this->createSellerUser();
        $supplierTwoCompany = $this->createCompany($supplierTwoUser, ['company_type' => 'supplier']);

        $acceptedQuotation = $this->createQuotation($rfq, $supplierOneCompany, $supplierOneUser);
        $rejectedQuotation = $this->createQuotation($rfq, $supplierTwoCompany, $supplierTwoUser, ['price' => 21]);

        $this->setActiveCompany($buyerCompany);

        $this->actingAs($buyerUser)->post(route('b2b.quotations.accept', $acceptedQuotation->id))
            ->assertRedirect();

        $this->assertDatabaseHas('b2b_quotations', [
            'id' => $acceptedQuotation->id,
            'status' => 'accepted',
        ]);
        $this->assertDatabaseHas('b2b_quotations', [
            'id' => $rejectedQuotation->id,
            'status' => 'rejected',
        ]);
        $this->assertDatabaseHas('b2b_rfqs', [
            'id' => $rfq->id,
            'status' => 'closed',
        ]);

        $purchaseOrder = B2BPurchaseOrder::where('quotation_id', $acceptedQuotation->id)->first();

        $this->assertNotNull($purchaseOrder);
        $this->assertSame($buyerCompany->id, $purchaseOrder->buyer_company_id);
        $this->assertSame($supplierOneCompany->id, $purchaseOrder->supplier_company_id);
        $this->assertSame('sent', $purchaseOrder->status);
    }

    public function test_negotiation_chat_can_be_opened_by_buyer_and_supplier_and_messages_can_be_sent(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $rfq = $this->createRfq($buyerCompany, $buyerUser, [
            'title' => 'Negotiation RFQ',
        ]);

        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, [
            'company_type' => 'supplier',
        ]);
        $this->createCompanyMember($supplierCompany, $supplierUser, 'sales_manager');

        $this->setActiveCompany($supplierCompany);

        $this->actingAs($supplierUser)->post(route('seller.b2b.rfqs.quote.store', $rfq->id), [
            'price' => 18.75,
            'currency' => 'USD',
            'moq' => 25,
            'lead_time_days' => 12,
            'shipping_terms' => 'Sea',
            'incoterm' => 'FOB',
            'payment_terms' => '50% advance',
            'message' => 'Initial supplier quotation',
        ])->assertRedirect(route('seller.b2b.quotations.index'));

        $quotation = $rfq->quotations()->latest('id')->firstOrFail();
        $negotiation = B2BNegotiation::where('quotation_id', $quotation->id)->first();

        $this->assertNotNull($negotiation);

        $this->actingAs($supplierUser)
            ->get(route('seller.b2b.quotations.show', $quotation->id))
            ->assertOk()
            ->assertSee(route('seller.b2b.negotiations.show', $negotiation->id), false)
            ->assertSee('Open Chat');

        $this->actingAs($supplierUser)
            ->get(route('seller.b2b.rfqs.quote', $rfq->id))
            ->assertOk()
            ->assertSee(route('seller.b2b.negotiations.show', $negotiation->id), false)
            ->assertSee('Open Chat');

        $this->setActiveCompany($buyerCompany);

        $this->actingAs($buyerUser)
            ->get(route('b2b.rfqs.show', $rfq->id))
            ->assertOk()
            ->assertSee(route('b2b.negotiations.show', $negotiation->id), false)
            ->assertSee('Open Chat');

        $this->actingAs($buyerUser)
            ->get(route('b2b.negotiations.show', $negotiation->id))
            ->assertOk()
            ->assertSee('Negotiation RFQ');

        $this->actingAs($buyerUser)
            ->post(route('b2b.negotiations.messages.store', $negotiation->id), [
                'message' => 'Buyer counter offer',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('b2b_negotiation_messages', [
            'negotiation_id' => $negotiation->id,
            'sender_user_id' => $buyerUser->id,
            'sender_role' => 'buyer',
            'message' => 'Buyer counter offer',
        ]);

        $this->setActiveCompany($supplierCompany);

        $this->actingAs($supplierUser)
            ->get(route('seller.b2b.negotiations.show', $negotiation->id))
            ->assertOk()
            ->assertSee('Buyer counter offer');

        $this->actingAs($supplierUser)
            ->post(route('seller.b2b.negotiations.messages.store', $negotiation->id), [
                'message' => 'Supplier revised offer',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('b2b_negotiation_messages', [
            'negotiation_id' => $negotiation->id,
            'sender_user_id' => $supplierUser->id,
            'sender_role' => 'supplier',
            'message' => 'Supplier revised offer',
        ]);
    }
}
