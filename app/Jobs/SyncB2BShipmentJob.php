<?php

namespace App\Jobs;

use App\Services\B2BShipmentTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncB2BShipmentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(protected int $shipmentId)
    {
    }

    public function handle(B2BShipmentTrackingService $trackingService): void
    {
        $trackingService->syncShipment($this->shipmentId);
    }
}
