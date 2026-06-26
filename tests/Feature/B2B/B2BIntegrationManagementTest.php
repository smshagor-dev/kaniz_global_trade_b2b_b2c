<?php

namespace Tests\Feature\B2B;

class B2BIntegrationManagementTest extends B2BFeatureTestCase
{
    public function test_forwarder_secret_is_generated_after_create(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->post(route('admin.b2b.freight-forwarders.store'), [
                'name' => 'MSC Integration',
                'driver' => 'msc',
                'provider_type' => 'ocean_carrier',
                'api_base_url' => 'https://api.freight.example.test',
                'api_key' => 'key',
                'api_secret' => 'secret',
                'supported_modes' => 'sea_freight',
                'supported_services' => 'port_to_port',
                'is_test_mode' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $forwarder = \App\Models\B2BFreightForwarder::latest('id')->first();

        $this->assertNotNull($forwarder);
        $this->assertNotEmpty($forwarder->webhook_secret);
    }

    public function test_integration_urls_are_visible_for_configured_provider(): void
    {
        $admin = $this->createAdminUser();
        $provider = $this->createShippingProvider([
            'provider_type' => 'api',
            'api_driver' => 'dhl',
            'api_key' => 'key',
            'api_secret' => 'secret',
            'account_number' => 'acct-1',
            'webhook_secret' => 'whsec_test_secret',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.b2b.shipping-providers.index'));

        $response->assertOk()
            ->assertSee(route('b2b.carrier-webhooks.handle', $provider->id), false)
            ->assertSee(route('b2b.carrier-webhooks.tracking', $provider->id), false)
            ->assertSee(route('admin.b2b.shipping-providers.test', $provider->id), false);
    }

    public function test_regenerating_provider_secret_changes_value(): void
    {
        $admin = $this->createAdminUser();
        $provider = $this->createShippingProvider([
            'provider_type' => 'api',
            'api_driver' => 'dhl',
            'api_key' => 'key',
            'api_secret' => 'secret',
            'account_number' => 'acct-1',
            'webhook_secret' => 'whsec_original_secret',
        ]);

        $original = $provider->webhook_secret;

        $this->actingAs($admin)
            ->post(route('admin.b2b.shipping-providers.regenerate-secret', $provider->id))
            ->assertRedirect();

        $this->assertNotSame($original, $provider->fresh()->webhook_secret);
    }
}
