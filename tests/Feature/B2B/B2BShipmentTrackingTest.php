<?php

namespace Tests\Feature\B2B;

use App\Models\B2BShipment;
use App\Services\B2BShipmentTrackingService;
use Illuminate\Support\Facades\Artisan;

class B2BShipmentTrackingTest extends B2BFeatureTestCase
{
    public function test_provider_assignment_works_for_supplier_shipment(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $provider = $this->createShippingProvider([
            'provider_type' => 'api',
            'api_driver' => 'dhl',
        ]);

        $shipment = $this->createShipment([
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'created_by' => $supplierUser->id,
        ]);

        $this->setActiveCompany($supplierCompany);

        $this->actingAs($supplierUser)->post(route('seller.b2b.shipments.tracking', $shipment->id), [
            'shipping_provider_id' => $provider->id,
            'tracking_number' => 'DHL-TRACK-001',
            'carrier_reference' => 'REF-001',
            'carrier_service' => 'Express',
            'tracking_url' => 'https://example.test/track/DHL-TRACK-001',
            'live_tracking_enabled' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('b2b_shipments', [
            'id' => $shipment->id,
            'shipping_provider_id' => $provider->id,
            'tracking_number' => 'DHL-TRACK-001',
            'carrier_reference' => 'REF-001',
            'carrier_service' => 'Express',
            'live_tracking_enabled' => 1,
        ]);
    }

    public function test_missing_credentials_returns_not_configured(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $provider = $this->createShippingProvider([
            'provider_type' => 'api',
            'api_driver' => 'dhl',
            'api_base_url' => null,
            'api_key' => null,
            'api_secret' => null,
        ]);

        $shipment = $this->createShipment([
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'shipping_provider_id' => $provider->id,
            'created_by' => $supplierUser->id,
            'live_tracking_enabled' => true,
        ]);

        $result = app(B2BShipmentTrackingService::class)->syncShipment($shipment->id);

        $this->assertFalse($result['success']);
        $this->assertSame('not_configured', $result['status']);
        $this->assertDatabaseHas('b2b_shipments', [
            'id' => $shipment->id,
            'sync_error' => 'DHL credentials are missing or incomplete.',
        ]);
    }

    public function test_sync_command_skips_delivered_shipments(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $provider = $this->createShippingProvider([
            'provider_type' => 'api',
            'api_driver' => 'dhl',
        ]);

        $deliveredShipment = $this->createShipment([
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'shipping_provider_id' => $provider->id,
            'created_by' => $supplierUser->id,
            'status' => 'delivered',
            'live_tracking_enabled' => true,
        ]);

        $activeShipment = $this->createShipment([
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'shipping_provider_id' => $provider->id,
            'created_by' => $supplierUser->id,
            'status' => 'in_transit',
            'live_tracking_enabled' => true,
        ]);

        Artisan::call('b2b:shipments:sync');

        $this->assertNull($deliveredShipment->fresh()->last_tracked_at);
        $this->assertNotNull($activeShipment->fresh()->last_tracked_at);
    }

    public function test_webhook_rejects_invalid_signature_when_secret_exists(): void
    {
        $provider = $this->createShippingProvider([
            'provider_type' => 'api',
            'api_driver' => 'custom',
            'webhook_secret' => 'secret-signature',
        ]);

        $this->post(route('b2b.carrier-webhooks.handle', $provider->id), [
            'tracking_number' => 'TRACK-FAIL',
        ], [
            'X-Webhook-Signature' => 'wrong-signature',
        ])->assertStatus(403);
    }

    public function test_buyer_can_view_tracking_timeline(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $provider = $this->createShippingProvider([
            'provider_type' => 'api',
            'api_driver' => 'custom',
        ]);

        $shipment = $this->createShipment([
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'shipping_provider_id' => $provider->id,
            'created_by' => $supplierUser->id,
            'tracking_number' => 'TRACK-VIEW-001',
            'tracking_url' => 'https://example.test/tracking/TRACK-VIEW-001',
            'carrier_status' => 'Out for delivery',
            'last_tracked_at' => now(),
        ]);

        $shipment->events()->create([
            'created_by' => $supplierUser->id,
            'status' => 'out_for_delivery',
            'title' => 'Out for delivery',
            'location' => 'Dhaka',
            'description' => 'Carrier out for delivery.',
            'event_at' => now(),
        ]);

        $this->setActiveCompany($buyerCompany);

        $this->actingAs($buyerUser)
            ->get(route('b2b.shipments.show', $shipment->id))
            ->assertOk()
            ->assertSee('TRACK-VIEW-001')
            ->assertSee('Out for delivery')
            ->assertSee('Open Tracking');
    }
}
