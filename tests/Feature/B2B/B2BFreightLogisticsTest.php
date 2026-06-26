<?php

namespace Tests\Feature\B2B;

use App\Jobs\SyncB2BFreightShipmentJob;
use App\Models\B2BContainerShipment;
use App\Models\B2BCustomsDocument;
use App\Models\B2BFreightQuote;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class B2BFreightLogisticsTest extends B2BFeatureTestCase
{
    public function test_admin_can_create_port_and_forwarder(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->postJson(route('admin.b2b.ports.store'), [
                'name' => 'Chattogram Port',
                'code' => 'CGP',
                'country' => 'Bangladesh',
                'city' => 'Chattogram',
                'unlocode' => 'BDCGP',
                'timezone' => 'Asia/Dhaka',
                'port_type' => 'sea',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($admin)
            ->postJson(route('admin.b2b.freight-forwarders.store'), [
                'name' => 'Maersk Sandbox',
                'driver' => 'maersk',
                'api_base_url' => 'https://api.freight.example.test',
                'api_key' => 'key',
                'api_secret' => 'secret',
                'environment' => 'sandbox',
                'supported_modes' => ['sea_freight'],
                'supported_services' => ['port_to_port'],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('b2b_ports', ['code' => 'CGP']);
        $this->assertDatabaseHas('b2b_freight_forwarders', ['name' => 'Maersk Sandbox', 'driver' => 'maersk']);
    }

    public function test_company_can_request_freight_quote_from_forwarder(): void
    {
        Http::fake([
            'https://api.freight.example.test/freight/quotes' => Http::response([
                'freight_cost' => 1200.50,
                'insurance_cost' => 85.25,
                'customs_estimate' => 125.00,
                'total_cost' => 1410.75,
                'currency' => 'USD',
                'estimated_days' => 18,
            ]),
        ]);

        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $originPort = $this->createPort(['name' => 'Shanghai', 'code' => 'CNSHA', 'country' => 'China', 'city' => 'Shanghai']);
        $destinationPort = $this->createPort(['name' => 'Chattogram', 'code' => 'BDCGP', 'country' => 'Bangladesh', 'city' => 'Chattogram']);
        $forwarder = $this->createFreightForwarder();

        $this->setActiveCompany($buyerCompany);

        $this->actingAs($buyerUser)
            ->postJson(route('b2b.freight-quotes.store'), [
                'forwarder_id' => $forwarder->id,
                'buyer_company_id' => $buyerCompany->id,
                'supplier_company_id' => $supplierCompany->id,
                'origin_country' => 'China',
                'origin_port_id' => $originPort->id,
                'destination_country' => 'Bangladesh',
                'destination_port_id' => $destinationPort->id,
                'freight_mode' => 'sea_freight',
                'service_type' => 'port_to_port',
                'incoterm' => 'FOB',
                'container_type' => '40HC',
                'container_count' => 1,
                'cargo_weight' => 1200,
                'cargo_volume' => 25,
                'goods_description' => 'Textile accessories',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'submitted');

        $quote = B2BFreightQuote::latest('id')->first();

        $this->assertNotNull($quote);
        $this->assertSame('submitted', $quote->status);
        $this->assertSame('1410.75', (string) $quote->total_cost);
    }

    public function test_forwarder_default_amounts_are_applied_when_driver_quote_is_unavailable(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $originPort = $this->createPort(['name' => 'Shanghai', 'code' => 'CNSHA', 'country' => 'China', 'city' => 'Shanghai']);
        $destinationPort = $this->createPort(['name' => 'Chattogram', 'code' => 'BDCGP', 'country' => 'Bangladesh', 'city' => 'Chattogram']);
        $forwarder = $this->createFreightForwarder([
            'driver' => 'custom',
            'default_freight_cost' => 900,
            'default_insurance_cost' => 60,
            'default_customs_estimate' => 40,
        ]);

        $this->setActiveCompany($buyerCompany);

        $this->actingAs($buyerUser)
            ->postJson(route('b2b.freight-quotes.store'), [
                'forwarder_id' => $forwarder->id,
                'buyer_company_id' => $buyerCompany->id,
                'supplier_company_id' => $supplierCompany->id,
                'origin_country' => 'China',
                'origin_port_id' => $originPort->id,
                'destination_country' => 'Bangladesh',
                'destination_port_id' => $destinationPort->id,
                'freight_mode' => 'sea_freight',
                'service_type' => 'port_to_port',
                'incoterm' => 'FOB',
                'container_type' => '40HC',
                'container_count' => 1,
                'cargo_weight' => 1200,
                'cargo_volume' => 25,
                'goods_description' => 'Textile accessories',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'priced');

        $quote = B2BFreightQuote::latest('id')->first();

        $this->assertNotNull($quote);
        $this->assertSame('900.00', (string) $quote->freight_cost);
        $this->assertSame('60.00', (string) $quote->insurance_cost);
        $this->assertSame('40.00', (string) $quote->customs_estimate);
        $this->assertSame('1000.00', (string) $quote->total_cost);
    }

    public function test_supplier_can_create_container_booking_from_quote(): void
    {
        Http::fake([
            'https://api.freight.example.test/freight/bookings' => Http::response([
                'booking_number' => 'BK-20260625-001',
                'bill_of_lading_number' => 'BL-20260625-001',
                'container_number' => 'MSKU1234567',
                'seal_number' => 'SEAL001',
                'vessel_name' => 'Northern Star',
                'voyage_number' => 'VS-88',
                'etd' => now()->addDay()->toIso8601String(),
                'eta' => now()->addDays(12)->toIso8601String(),
                'status' => 'booked',
            ]),
        ]);

        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $this->setActiveCompany($supplierCompany);

        $originPort = $this->createPort(['name' => 'Shanghai', 'code' => 'CNSHA', 'country' => 'China']);
        $destinationPort = $this->createPort(['name' => 'Chattogram', 'code' => 'BDCGP', 'country' => 'Bangladesh']);
        $forwarder = $this->createFreightForwarder();
        $quote = $this->createFreightQuote([
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'forwarder_id' => $forwarder->id,
            'created_by' => $buyerUser->id,
            'origin_port_id' => $originPort->id,
            'destination_port_id' => $destinationPort->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($supplierUser)
            ->postJson(route('seller.b2b.container-shipments.store', $quote->id), [
                'pickup_address' => 'Shanghai warehouse',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'booked');

        $shipment = B2BContainerShipment::latest('id')->first();
        $this->assertNotNull($shipment);
        $this->assertSame('MSKU1234567', $shipment->container_number);
        $this->assertDatabaseHas('b2b_container_events', [
            'container_shipment_id' => $shipment->id,
            'event_type' => 'gate_in',
        ]);
    }

    public function test_public_container_tracking_returns_timeline(): void
    {
        $forwarder = $this->createFreightForwarder();
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $quote = $this->createFreightQuote([
            'buyer_company_id' => $buyerCompany->id,
            'forwarder_id' => $forwarder->id,
            'created_by' => $buyerUser->id,
        ]);
        $shipment = $this->createContainerShipment([
            'freight_quote_id' => $quote->id,
            'forwarder_id' => $forwarder->id,
            'container_number' => 'MSKU7654321',
        ]);

        $shipment->events()->create([
            'event_type' => 'loaded_on_vessel',
            'port_location' => 'Shanghai',
            'description' => 'Loaded on vessel',
            'source_provider' => $forwarder->name,
            'event_at' => now(),
        ]);

        $this->getJson(route('b2b.container-tracking.track', ['container_number' => 'MSKU7654321']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('shipment.container_number', 'MSKU7654321')
            ->assertJsonCount(1, 'timeline');
    }

    public function test_customs_document_upload_attaches_to_freight_quote(): void
    {
        Storage::fake('local');

        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $this->setActiveCompany($buyerCompany);

        $quote = $this->createFreightQuote([
            'buyer_company_id' => $buyerCompany->id,
            'created_by' => $buyerUser->id,
        ]);

        $this->actingAs($buyerUser)
            ->post(route('b2b.customs-documents.store', ['type' => 'freight-quote', 'id' => $quote->id]), [
                'document_type' => 'commercial_invoice',
                'title' => 'Commercial Invoice',
                'file' => UploadedFile::fake()->create('commercial-invoice.pdf', 64, 'application/pdf'),
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $document = B2BCustomsDocument::where('documentable_type', B2BFreightQuote::class)
            ->where('documentable_id', $quote->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($document);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_freight_webhook_rejects_invalid_signature(): void
    {
        $forwarder = $this->createFreightForwarder([
            'driver' => 'custom',
            'webhook_secret' => 'expected-secret',
        ]);

        $this->postJson(route('b2b.freight-webhooks.handle', $forwarder->id), [
            'container_number' => 'MSKU9999999',
            'event' => 'Delivered',
        ], [
            'X-Webhook-Signature' => 'wrong-secret',
        ])->assertStatus(403);
    }

    public function test_freight_sync_command_skips_delivered_shipments(): void
    {
        Http::fake([
            'https://api.freight.example.test/freight/track/*' => Http::response([
                'status' => 'vessel_arrived',
                'eta' => now()->addDays(2)->toIso8601String(),
                'events' => [[
                    'event' => 'Vessel Arrived',
                    'port_location' => 'Singapore',
                    'description' => 'Arrived at transshipment hub',
                    'event_at' => now()->toIso8601String(),
                ]],
            ]),
        ]);

        $forwarder = $this->createFreightForwarder();
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $deliveredQuote = $this->createFreightQuote([
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'forwarder_id' => $forwarder->id,
            'created_by' => $buyerUser->id,
        ]);
        $activeQuote = $this->createFreightQuote([
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'forwarder_id' => $forwarder->id,
            'created_by' => $buyerUser->id,
        ]);

        $deliveredShipment = $this->createContainerShipment([
            'freight_quote_id' => $deliveredQuote->id,
            'forwarder_id' => $forwarder->id,
            'container_number' => 'DONE1234567',
            'status' => 'delivered',
        ]);

        $activeShipment = $this->createContainerShipment([
            'freight_quote_id' => $activeQuote->id,
            'forwarder_id' => $forwarder->id,
            'container_number' => 'LIVE1234567',
            'status' => 'vessel_departed',
        ]);

        Artisan::call('b2b:freight:sync');

        $this->assertNull($deliveredShipment->fresh()->last_synced_at);
        $this->assertNotNull($activeShipment->fresh()->last_synced_at);
    }

    public function test_freight_sync_command_can_dispatch_queue_jobs(): void
    {
        Bus::fake();

        $forwarder = $this->createFreightForwarder();
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $quote = $this->createFreightQuote([
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'forwarder_id' => $forwarder->id,
            'created_by' => $buyerUser->id,
        ]);
        $shipment = $this->createContainerShipment([
            'freight_quote_id' => $quote->id,
            'forwarder_id' => $forwarder->id,
            'container_number' => 'QUEU1234567',
            'status' => 'booked',
        ]);

        Artisan::call('b2b:freight:sync', ['--queue' => true]);

        Bus::assertDispatched(SyncB2BFreightShipmentJob::class, function ($job) use ($shipment) {
            $reflection = new \ReflectionClass($job);
            $property = $reflection->getProperty('containerShipmentId');
            $property->setAccessible(true);

            return $property->getValue($job) === $shipment->id;
        });
    }
}
