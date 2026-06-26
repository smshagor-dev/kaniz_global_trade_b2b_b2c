<?php

namespace Tests\Feature\B2B;

use App\Models\B2BSampleOrder;
use App\Models\B2BShipment;
use App\Models\B2BShippingQuote;
use App\Models\B2BTradeDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class B2BSampleShipmentTest extends B2BFeatureTestCase
{
    public function test_sample_order_shipping_quote_and_shipment_flow(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $this->createCompanyMember($supplierCompany, $supplierUser, 'sales_manager');
        $provider = $this->createShippingProvider();

        $this->setActiveCompany($buyerCompany);
        $this->actingAs($buyerUser)->post(route('b2b.sample-orders.store'), [
            'supplier_company_id' => $supplierCompany->id,
            'quantity' => 3,
            'unit' => 'pcs',
            'currency' => 'USD',
            'notes' => 'Need evaluation sample',
        ])->assertRedirect();

        $sampleOrder = B2BSampleOrder::latest('id')->first();
        $this->assertNotNull($sampleOrder);
        $this->assertSame('requested', $sampleOrder->status);

        $this->setActiveCompany($supplierCompany);
        $this->actingAs($supplierUser)->post(route('seller.b2b.sample-orders.accept', $sampleOrder->id), [
            'sample_price' => 25,
        ])->assertRedirect();

        $this->assertDatabaseHas('b2b_sample_orders', [
            'id' => $sampleOrder->id,
            'status' => 'accepted',
            'sample_price' => 25,
        ]);

        $this->actingAs($supplierUser)->post(route('seller.b2b.shipping-quotes.sample-orders.store', $sampleOrder->id), [
            'shipping_provider_id' => $provider->id,
            'transport_mode' => 'courier',
            'origin_country' => 'China',
            'destination_country' => 'Bangladesh',
            'incoterm' => 'FOB',
            'currency' => 'USD',
            'estimated_days' => 7,
            'shipping_cost' => 30,
            'insurance_amount' => 5,
            'customs_estimate' => 10,
            'notes' => 'Express sample shipment',
        ])->assertRedirect();

        $quote = B2BShippingQuote::where('sample_order_id', $sampleOrder->id)->latest('id')->first();
        $this->assertNotNull($quote);

        $this->setActiveCompany($buyerCompany);
        $this->actingAs($buyerUser)->post(route('b2b.shipping-quotes.select', $quote->id))
            ->assertRedirect();

        $this->assertDatabaseHas('b2b_shipping_quotes', [
            'id' => $quote->id,
            'status' => 'selected',
        ]);
        $this->assertDatabaseHas('b2b_sample_orders', [
            'id' => $sampleOrder->id,
            'status' => 'payment_pending',
        ]);

        $this->actingAs($buyerUser)->post(route('b2b.sample-orders.pay', $sampleOrder->id), [
            'payment_reference' => 'PAY-12345',
        ])->assertRedirect();

        $this->assertDatabaseHas('b2b_sample_orders', [
            'id' => $sampleOrder->id,
            'status' => 'paid',
            'payment_reference' => 'PAY-12345',
        ]);

        $this->setActiveCompany($supplierCompany);
        $this->actingAs($supplierUser)->post(route('seller.b2b.shipments.store'), [
            'sample_order_id' => $sampleOrder->id,
            'shipping_quote_id' => $quote->id,
            'shipping_provider_id' => $provider->id,
            'transport_mode' => 'courier',
            'incoterm' => 'FOB',
            'tracking_number' => 'TRACK-001',
            'origin_country' => 'China',
            'destination_country' => 'Bangladesh',
            'estimated_departure' => now()->toDateString(),
            'estimated_arrival' => now()->addDays(7)->toDateString(),
            'notes' => 'Shipment created',
        ])->assertRedirect();

        $shipment = B2BShipment::where('sample_order_id', $sampleOrder->id)->latest('id')->first();
        $this->assertNotNull($shipment);
        $this->assertSame('preparing', $shipment->status);

        $this->actingAs($supplierUser)->post(route('seller.b2b.shipments.status', $shipment->id), [
            'status' => 'in_transit',
            'title' => 'Left origin',
            'location' => 'Shanghai',
            'description' => 'Package departed',
            'event_at' => now()->addDay()->toDateTimeString(),
        ])->assertRedirect();

        $this->actingAs($supplierUser)->post(route('seller.b2b.shipments.status', $shipment->id), [
            'status' => 'delivered',
            'title' => 'Delivered',
            'location' => 'Dhaka',
            'description' => 'Package delivered',
            'event_at' => now()->addDays(5)->toDateTimeString(),
        ])->assertRedirect();

        $shipment->refresh();
        $this->assertSame('delivered', $shipment->status);
        $this->assertCount(3, $shipment->events()->get());
    }

    public function test_shipping_quote_uses_provider_default_amounts_when_manual_amounts_are_omitted(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $this->createCompanyMember($supplierCompany, $supplierUser, 'sales_manager');
        $provider = $this->createShippingProvider([
            'default_shipping_cost' => 42,
            'default_insurance_amount' => 8,
            'default_customs_estimate' => 5,
        ]);

        $sampleOrder = $this->createSampleOrder($buyerCompany, $buyerUser, $supplierCompany, $supplierUser, [
            'status' => 'accepted',
        ]);

        $this->setActiveCompany($supplierCompany);
        $this->actingAs($supplierUser)->post(route('seller.b2b.shipping-quotes.sample-orders.store', $sampleOrder->id), [
            'shipping_provider_id' => $provider->id,
            'transport_mode' => 'courier',
            'origin_country' => 'China',
            'destination_country' => 'Bangladesh',
            'incoterm' => 'FOB',
            'currency' => 'USD',
            'estimated_days' => 5,
            'notes' => 'Use provider defaults',
        ])->assertRedirect();

        $quote = B2BShippingQuote::where('sample_order_id', $sampleOrder->id)->latest('id')->first();

        $this->assertNotNull($quote);
        $this->assertSame('42.00', (string) $quote->shipping_cost);
        $this->assertSame('8.00', (string) $quote->insurance_amount);
        $this->assertSame('5.00', (string) $quote->customs_estimate);
    }

    public function test_trade_document_upload_attaches_to_shipment(): void
    {
        Storage::fake('local');

        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $shipment = $this->createShipment([
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'created_by' => $supplierUser->id,
        ]);

        $this->setActiveCompany($supplierCompany);

        $response = $this->actingAs($supplierUser)->post(route('b2b.trade-documents.store', ['type' => 'shipment', 'id' => $shipment->id]), [
            'document_type' => 'commercial_invoice',
            'title' => 'Commercial Invoice',
            'file' => UploadedFile::fake()->create('invoice.pdf', 64, 'application/pdf'),
        ]);

        $response->assertRedirect();

        $document = B2BTradeDocument::where('documentable_type', B2BShipment::class)
            ->where('documentable_id', $shipment->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($document);
        Storage::disk('local')->assertExists($document->file_path);
    }
}
