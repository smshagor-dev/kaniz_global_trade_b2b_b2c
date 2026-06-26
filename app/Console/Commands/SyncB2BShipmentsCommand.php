<?php

namespace App\Console\Commands;

use App\Jobs\SyncB2BShipmentJob;
use App\Models\B2BShipment;
use App\Services\B2BShipmentTrackingService;
use Illuminate\Console\Command;

class SyncB2BShipmentsCommand extends Command
{
    protected $signature = 'b2b:shipments:sync';

    protected $description = 'Sync live B2B shipment tracking statuses from carrier providers.';

    public function handle(B2BShipmentTrackingService $trackingService): int
    {
        $shipments = B2BShipment::query()
            ->where('live_tracking_enabled', true)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->pluck('id');

        if ($shipments->isEmpty()) {
            $this->info('No live B2B shipments are due for sync.');
            return self::SUCCESS;
        }

        $queueDriver = (string) config('queue.default', 'sync');

        if ($queueDriver !== 'sync') {
            foreach ($shipments as $shipmentId) {
                SyncB2BShipmentJob::dispatch($shipmentId);
            }

            $this->info('Queued ' . $shipments->count() . ' B2B shipment sync jobs.');
            return self::SUCCESS;
        }

        $results = [
            'synced' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($shipments as $shipmentId) {
            $result = $trackingService->syncShipment($shipmentId);

            if ($result['success'] ?? false) {
                $results['synced']++;
            } elseif (in_array($result['status'] ?? null, ['manual', 'not_configured'], true)) {
                $results['skipped']++;
            } else {
                $results['failed']++;
            }
        }

        $this->info(sprintf(
            'B2B shipment sync complete. Synced: %d, Skipped: %d, Failed: %d.',
            $results['synced'],
            $results['skipped'],
            $results['failed']
        ));

        return $results['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
