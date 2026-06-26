<?php

namespace Tests\Feature\B2B;

use App\Models\B2BShippingProvider;
use Tests\TestCase;

class B2BShippingProviderModeDriverTest extends TestCase
{
    public function test_sea_freight_mode_accepts_maersk_driver(): void
    {
        $drivers = B2BShippingProvider::driversForMode('sea_freight');

        $this->assertContains('maersk', $drivers);
        $this->assertContains('msc', $drivers);
        $this->assertNotContains('fedex', $drivers);
    }

    public function test_courier_mode_keeps_courier_drivers(): void
    {
        $drivers = B2BShippingProvider::driversForMode('courier');

        $this->assertContains('dhl', $drivers);
        $this->assertContains('fedex', $drivers);
        $this->assertNotContains('maersk', $drivers);
    }
}
