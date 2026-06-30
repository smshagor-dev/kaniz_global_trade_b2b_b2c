<?php

namespace Tests\Feature\B2B;

use App\Models\B2BCompany;
use App\Models\B2BEscrow;
use App\Models\B2BLetterOfCredit;
use App\Models\B2BProformaInvoice;
use App\Models\B2BSettlement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class B2BPurchaseOrderInvoiceTest extends B2BFeatureTestCase
{
    public function test_supplier_can_accept_purchase_order_and_create_proforma_invoice(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $this->activatePackage($buyerCompany);
        $rfq = $this->createRfq($buyerCompany, $buyerUser);

        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $this->activatePackage($supplierCompany);

        $quotation = $this->createQuotation($rfq, $supplierCompany, $supplierUser);
        $purchaseOrder = $this->createPurchaseOrder($quotation);

        $this->setActiveCompany($supplierCompany);

        $this->actingAs($supplierUser)->post(route('seller.b2b.purchase-orders.accept', $purchaseOrder->id))
            ->assertRedirect();

        $this->assertDatabaseHas('b2b_purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => 'accepted',
        ]);

        $this->actingAs($supplierUser)->post(route('seller.b2b.proforma-invoices.store', $purchaseOrder->id), [
            'currency' => 'USD',
            'incoterm' => 'FOB',
            'tax_amount' => 5,
            'shipping_amount' => 15,
            'discount_amount' => 0,
            'valid_until' => now()->addDays(10)->toDateString(),
            'notes' => 'PI notes',
            'status' => 'sent',
            'items' => [[
                'product_id' => null,
                'product_name' => 'Bulk line',
                'description' => 'Shipment ready',
                'quantity' => 100,
                'unit' => 'pcs',
                'unit_price' => 20,
                'tax_amount' => 5,
                'discount_amount' => 0,
                'line_total' => 2000,
            ]],
        ])->assertRedirect();

        $invoice = B2BProformaInvoice::where('purchase_order_id', $purchaseOrder->id)->first();

        $this->assertNotNull($invoice);
        $this->assertSame('sent', $invoice->status);
        $this->assertSame('FOB', $invoice->incoterm);
    }

    public function test_supplier_can_reject_purchase_order(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $this->activatePackage($buyerCompany);
        $rfq = $this->createRfq($buyerCompany, $buyerUser);

        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $this->activatePackage($supplierCompany);

        $quotation = $this->createQuotation($rfq, $supplierCompany, $supplierUser);
        $purchaseOrder = $this->createPurchaseOrder($quotation);

        $this->setActiveCompany($supplierCompany);

        $this->actingAs($supplierUser)->post(route('seller.b2b.purchase-orders.reject', $purchaseOrder->id))
            ->assertRedirect();

        $this->assertDatabaseHas('b2b_purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => 'rejected',
        ]);
    }

    public function test_buyer_can_fund_and_release_escrow_end_to_end(): void
    {
        foreach ([
            'b2b_escrow_fee_enabled' => 1,
            'b2b_escrow_fee_type' => 'percentage',
            'b2b_escrow_fee_percent' => 1,
            'b2b_escrow_fee_fixed' => 0,
        ] as $type => $value) {
            DB::table('business_settings')->updateOrInsert(
                ['type' => $type],
                ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }
        Cache::forget('business_settings');

        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $this->activatePackage($buyerCompany);
        $rfq = $this->createRfq($buyerCompany, $buyerUser);

        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $this->activatePackage($supplierCompany);

        $quotation = $this->createQuotation($rfq, $supplierCompany, $supplierUser);
        $purchaseOrder = $this->createPurchaseOrder($quotation);

        $this->setActiveCompany($supplierCompany);

        $this->actingAs($supplierUser)->post(route('seller.b2b.purchase-orders.accept', $purchaseOrder->id))
            ->assertRedirect();

        $this->actingAs($supplierUser)->post(route('seller.b2b.proforma-invoices.store', $purchaseOrder->id), [
            'currency' => 'USD',
            'incoterm' => 'FOB',
            'tax_amount' => 5,
            'shipping_amount' => 15,
            'discount_amount' => 0,
            'valid_until' => now()->addDays(10)->toDateString(),
            'notes' => 'PI notes',
            'status' => 'sent',
            'items' => [[
                'product_id' => null,
                'product_name' => 'Bulk line',
                'description' => 'Shipment ready',
                'quantity' => 100,
                'unit' => 'pcs',
                'unit_price' => 20,
                'tax_amount' => 5,
                'discount_amount' => 0,
                'line_total' => 2000,
            ]],
        ])->assertRedirect();

        $invoice = B2BProformaInvoice::where('purchase_order_id', $purchaseOrder->id)->firstOrFail();

        $this->setActiveCompany($buyerCompany);

        $this->actingAs($buyerUser)->post(route('b2b.proforma-invoices.accept', $invoice->id))
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame('accepted', $invoice->status);
        $this->assertSame('awaiting_payment', $invoice->escrow_status);

        $this->actingAs($buyerUser)->post(route('b2b.proforma-invoices.fund', $invoice->id), [
            'escrow_payment_reference' => 'ESCROW-REF-1001',
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('funded', $invoice->escrow_status);
        $this->assertSame('ESCROW-REF-1001', $invoice->escrow_payment_reference);

        $this->actingAs($buyerUser)->post(route('b2b.purchase-orders.complete', $purchaseOrder->id))
            ->assertRedirect();

        $this->assertDatabaseHas('b2b_purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => 'accepted',
        ]);

        $this->actingAs($buyerUser)->post(route('b2b.proforma-invoices.release', $invoice->id), [
            'escrow_resolution_notes' => 'Goods verified.',
        ])->assertRedirect();

        $invoice->refresh();
        $purchaseOrder->refresh();

        $this->assertSame('released', $invoice->escrow_status);
        $this->assertSame('released', $invoice->escrow_resolution);
        $this->assertSame('Goods verified.', $invoice->escrow_resolution_notes);
        $this->assertNotNull($invoice->escrow_released_at);
        $this->assertNotNull($invoice->supplier_paid_out_at);
        $this->assertSame('completed', $purchaseOrder->status);
    }

    public function test_supplier_trade_finance_dashboard_renders_scoped_dynamic_data(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $this->activatePackage($buyerCompany);
        $rfq = $this->createRfq($buyerCompany, $buyerUser);

        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $this->activatePackage($supplierCompany);

        $otherSupplierUser = $this->createSellerUser();
        $otherSupplierCompany = $this->createCompany($otherSupplierUser, ['company_type' => 'supplier']);
        $this->activatePackage($otherSupplierCompany);

        $quotation = $this->createQuotation($rfq, $supplierCompany, $supplierUser);
        $purchaseOrder = $this->createPurchaseOrder($quotation, ['status' => 'accepted']);
        $invoice = $this->createProformaInvoice($purchaseOrder, ['status' => 'accepted']);

        $lc = B2BLetterOfCredit::create([
            'purchase_order_id' => $purchaseOrder->id,
            'proforma_invoice_id' => $invoice->id,
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'lc_number' => 'LC-1001',
            'issuing_bank' => 'Test Bank',
            'expiry_date' => now()->addDays(20)->toDateString(),
            'amount' => 5000,
            'currency' => 'USD',
            'status' => 'requested',
        ]);

        $escrow = B2BEscrow::create([
            'reference_type' => B2BProformaInvoice::class,
            'reference_id' => $invoice->id,
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'currency' => 'USD',
            'settlement_currency' => 'USD',
            'exchange_rate_snapshot' => 1,
            'settlement_exchange_rate_snapshot' => 1,
            'amount' => 5000,
            'funded_amount' => 5000,
            'released_amount' => 5000,
            'status' => 'released',
            'released_at' => now(),
        ]);

        B2BSettlement::create([
            'supplier_company_id' => $supplierCompany->id,
            'escrow_id' => $escrow->id,
            'settlement_method' => 'bank_transfer',
            'currency' => 'USD',
            'amount' => 5000,
            'fees' => 50,
            'net_amount' => 4950,
            'status' => 'pending_approval',
            'requested_by' => $supplierUser->id,
            'requested_at' => now(),
        ]);

        DB::table('b2b_finance_disputes')->insert([
            'reference_type' => B2BProformaInvoice::class,
            'reference_id' => $invoice->id,
            'purchase_order_id' => $purchaseOrder->id,
            'proforma_invoice_id' => $invoice->id,
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'created_by' => $supplierUser->id,
            'category' => 'payment',
            'title' => 'Payment hold',
            'description' => 'Awaiting payout review',
            'status' => 'open',
            'escrow_hold' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherQuotation = $this->createQuotation($rfq, $otherSupplierCompany, $otherSupplierUser);
        $otherPurchaseOrder = $this->createPurchaseOrder($otherQuotation);
        $this->createProformaInvoice($otherPurchaseOrder);

        $this->setActiveCompany($supplierCompany);

        $response = $this->actingAs($supplierUser)->get(route('seller.b2b.trade-finance.dashboard'));

        $response->assertOk();
        $response->assertSee('Supplier Trade Finance Dashboard');
        $response->assertSee($supplierCompany->company_name);
        $response->assertSee($purchaseOrder->po_number);
        $response->assertDontSee($otherPurchaseOrder->po_number);
        $response->assertSee($lc->lc_number);
        $response->assertSee('Payment hold');
    }

    public function test_supplier_can_update_own_letter_of_credit_status_and_settlement_requires_released_escrow(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $this->activatePackage($buyerCompany);
        $rfq = $this->createRfq($buyerCompany, $buyerUser);

        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $this->activatePackage($supplierCompany);

        $quotation = $this->createQuotation($rfq, $supplierCompany, $supplierUser);
        $purchaseOrder = $this->createPurchaseOrder($quotation, ['status' => 'accepted']);
        $invoice = $this->createProformaInvoice($purchaseOrder, ['status' => 'accepted']);

        $lc = B2BLetterOfCredit::create([
            'purchase_order_id' => $purchaseOrder->id,
            'proforma_invoice_id' => $invoice->id,
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'lc_number' => 'LC-2001',
            'issuing_bank' => 'Trade Bank',
            'expiry_date' => now()->addDays(20)->toDateString(),
            'amount' => 1200,
            'currency' => 'USD',
            'status' => 'requested',
        ]);

        $escrow = B2BEscrow::create([
            'reference_type' => B2BProformaInvoice::class,
            'reference_id' => $invoice->id,
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'currency' => 'USD',
            'settlement_currency' => 'USD',
            'exchange_rate_snapshot' => 1,
            'settlement_exchange_rate_snapshot' => 1,
            'amount' => 1200,
            'funded_amount' => 1200,
            'status' => 'funded',
            'funded_at' => now(),
        ]);

        $this->setActiveCompany($supplierCompany);

        $this->actingAs($supplierUser)->post(route('seller.b2b.trade-finance.letters-of-credit.status', $lc->id), [
            'status' => 'documents_uploaded',
        ])->assertRedirect();

        $lc->refresh();
        $this->assertSame('documents_uploaded', $lc->status);

        $this->actingAs($supplierUser)->post(route('seller.b2b.trade-finance.settlements.store', $escrow->id), [
            'settlement_method' => 'bank_transfer',
        ])->assertRedirect();

        $this->assertDatabaseMissing('b2b_settlements', [
            'escrow_id' => $escrow->id,
        ]);

        $escrow->update([
            'status' => 'released',
            'released_amount' => 1200,
            'released_at' => now(),
        ]);

        $this->actingAs($supplierUser)->post(route('seller.b2b.trade-finance.settlements.store', $escrow->id), [
            'settlement_method' => 'bank_transfer',
            'reference' => 'SET-2001',
        ])->assertRedirect();

        $this->assertDatabaseHas('b2b_settlements', [
            'escrow_id' => $escrow->id,
            'supplier_company_id' => $supplierCompany->id,
            'status' => 'pending_approval',
            'reference' => 'SET-2001',
        ]);
    }

    protected function activatePackage(B2BCompany $company): void
    {
        $packageId = DB::table('b2b_packages')->insertGetId([
            'name' => 'Test Package ' . $company->id,
            'package_for' => $company->isSupplierSide() ? 'supplier' : 'buyer',
            'amount' => 0,
            'duration' => 30,
            'rfq_limit' => 0,
            'quotation_limit' => 0,
            'product_limit' => 0,
            'member_limit' => 10,
            'priority_listing' => 0,
            'featured_profile' => 0,
            'verified_badge' => 0,
            'analytics_access' => 1,
            'dedicated_support' => 0,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $company->update([
            'b2b_package_id' => $packageId,
            'package_started_at' => now(),
            'package_expires_at' => now()->addDays(30),
        ]);
    }
}
