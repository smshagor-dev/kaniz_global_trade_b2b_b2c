<?php

namespace Tests\Feature\B2B;

use App\Jobs\SyncB2BShipmentJob;
use App\Models\B2BTradeDocument;
use App\Services\Carriers\CarrierManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class B2BCarrierIntegrationTest extends B2BFeatureTestCase
{
    public function test_fedex_connection_validation_uses_oauth_token(): void
    {
        Http::fake([
            'https://apis-sandbox.fedex.com/oauth/token' => Http::response([
                'access_token' => 'fedex-test-token',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ]),
        ]);

        $provider = $this->createShippingProvider([
            'provider_type' => 'api',
            'api_driver' => 'fedex',
            'api_key' => 'client-id',
            'api_secret' => 'client-secret',
            'account_number' => '123456789',
            'is_test_mode' => true,
        ]);

        $result = app(CarrierManager::class)->testConnection($provider);

        $this->assertTrue($result['success']);
        $this->assertSame('validated', $result['status']);
        Http::assertSentCount(1);
    }

    public function test_supplier_can_lookup_live_rates_for_provider(): void
    {
        Http::fake([
            'https://apis-sandbox.fedex.com/oauth/token' => Http::response([
                'access_token' => 'fedex-test-token',
            ]),
            'https://apis-sandbox.fedex.com/rate/v1/rates/quotes' => Http::response([
                'output' => [
                    'rateReplyDetails' => [[
                        'serviceType' => 'FEDEX_INTERNATIONAL_PRIORITY',
                        'serviceName' => 'International Priority',
                        'ratedShipmentDetails' => [[
                            'totalNetCharge' => [
                                'currency' => 'USD',
                                'amount' => 42.75,
                            ],
                        ]],
                        'commit' => [
                            'transitDays' => 'TWO_DAYS',
                        ],
                    ]],
                ],
            ]),
        ]);

        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $this->setActiveCompany($supplierCompany);

        $provider = $this->createShippingProvider([
            'provider_type' => 'api',
            'api_driver' => 'fedex',
            'api_key' => 'client-id',
            'api_secret' => 'client-secret',
            'account_number' => '123456789',
            'is_test_mode' => true,
        ]);

        $this->actingAs($supplierUser)
            ->postJson(route('seller.b2b.shipping-providers.rates', $provider->id), [
                'shipper' => [
                    'companyName' => 'Supplier Co',
                    'cityName' => 'Dhaka',
                    'countryCode' => 'BD',
                ],
                'recipient' => [
                    'companyName' => 'Buyer Co',
                    'cityName' => 'Dubai',
                    'countryCode' => 'AE',
                ],
                'weight' => 5.5,
                'length' => 20,
                'width' => 15,
                'height' => 10,
                'currency' => 'USD',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('rates.0.amount', 42.75);
    }

    public function test_supplier_can_create_carrier_shipment_and_store_label_document(): void
    {
        Storage::fake('local');

        Http::fake([
            'https://apis-sandbox.fedex.com/oauth/token' => Http::response([
                'access_token' => 'fedex-test-token',
            ]),
            'https://apis-sandbox.fedex.com/ship/v1/shipments' => Http::response([
                'output' => [
                    'transactionShipments' => [[
                        'masterTrackingNumber' => '777712341234',
                        'serviceType' => 'FEDEX_INTERNATIONAL_PRIORITY',
                        'pieceResponses' => [[
                            'packageDocuments' => [[
                                'encodedLabel' => base64_encode('fedex-label-pdf'),
                            ]],
                        ]],
                    ]],
                ],
            ]),
        ]);

        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $this->setActiveCompany($supplierCompany);

        $provider = $this->createShippingProvider([
            'provider_type' => 'api',
            'api_driver' => 'fedex',
            'api_key' => 'client-id',
            'api_secret' => 'client-secret',
            'account_number' => '123456789',
            'is_test_mode' => true,
        ]);

        $shipment = $this->createShipment([
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'shipping_provider_id' => $provider->id,
            'created_by' => $supplierUser->id,
            'total_weight' => 5,
            'package_length' => 20,
            'package_width' => 10,
            'package_height' => 15,
            'currency' => 'USD',
        ]);

        $this->actingAs($supplierUser)
            ->postJson(route('seller.b2b.shipments.carrier.create', $shipment->id), [
                'carrier_payload' => [
                    'shipper' => [
                        'companyName' => 'Supplier Co',
                        'cityName' => 'Dhaka',
                        'countryCode' => 'BD',
                    ],
                    'recipient' => [
                        'companyName' => 'Buyer Co',
                        'cityName' => 'Dubai',
                        'countryCode' => 'AE',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $shipment->refresh();

        $this->assertSame('777712341234', $shipment->tracking_number);
        $this->assertDatabaseHas('b2b_trade_documents', [
            'documentable_type' => \App\Models\B2BShipment::class,
            'documentable_id' => $shipment->id,
            'document_type' => 'shipping_label',
        ]);

        $document = B2BTradeDocument::where('documentable_type', \App\Models\B2BShipment::class)
            ->where('documentable_id', $shipment->id)
            ->where('document_type', 'shipping_label')
            ->first();

        $this->assertNotNull($document);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_sync_job_updates_shipment_from_carrier_response(): void
    {
        Http::fake([
            'https://apis-sandbox.fedex.com/oauth/token' => Http::response([
                'access_token' => 'fedex-test-token',
            ]),
            'https://apis-sandbox.fedex.com/track/v1/trackingnumbers' => Http::response([
                'output' => [
                    'completeTrackResults' => [[
                        'trackResults' => [[
                            'trackingNumberInfo' => ['trackingNumber' => '777712341234'],
                            'serviceDetail' => ['type' => 'FEDEX_INTERNATIONAL_PRIORITY'],
                            'latestStatusDetail' => [
                                'statusByLocale' => 'Delivered',
                                'scanLocation' => [
                                    'city' => 'Dubai',
                                    'countryCode' => 'AE',
                                ],
                            ],
                            'deliveryDetails' => [
                                'receivedByName' => 'Warehouse Team',
                            ],
                            'scanEvents' => [[
                                'eventDescription' => 'Delivered',
                                'derivedStatusCode' => 'DL',
                                'date' => now()->toIso8601String(),
                                'scanLocation' => [
                                    'city' => 'Dubai',
                                    'countryCode' => 'AE',
                                ],
                            ]],
                        ]],
                    ]],
                ],
            ]),
        ]);

        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);

        $provider = $this->createShippingProvider([
            'provider_type' => 'api',
            'api_driver' => 'fedex',
            'api_key' => 'client-id',
            'api_secret' => 'client-secret',
            'account_number' => '123456789',
            'is_test_mode' => true,
        ]);

        $shipment = $this->createShipment([
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'shipping_provider_id' => $provider->id,
            'created_by' => $supplierUser->id,
            'tracking_number' => '777712341234',
            'live_tracking_enabled' => true,
            'status' => 'in_transit',
        ]);

        (new SyncB2BShipmentJob($shipment->id))->handle(app(\App\Services\B2BShipmentTrackingService::class));

        $shipment->refresh();

        $this->assertSame('delivered', $shipment->status);
        $this->assertSame('Delivered', $shipment->carrier_status);
        $this->assertDatabaseHas('b2b_shipment_events', [
            'shipment_id' => $shipment->id,
            'status' => 'delivered',
        ]);
    }
}
