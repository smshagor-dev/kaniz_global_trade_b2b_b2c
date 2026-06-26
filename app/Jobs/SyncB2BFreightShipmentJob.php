<?php

namespace App\Jobs;

use App\Services\B2BFreightService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncB2BFreightShipmentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(protected int $containerShipmentId)
    {
    }

    public function handle(B2BFreightService $freightService): void
    {
        $shipment = \App\Models\B2BContainerShipment::with('forwarder')->find($this->containerShipmentId);
        if (!$shipment) {
            return;
        }

        $freightService->syncContainerShipment($shipment);
    }
}
