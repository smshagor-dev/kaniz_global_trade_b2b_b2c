<?php

namespace App\Console\Commands;

use App\Jobs\SyncB2BFreightShipmentJob;
use App\Models\B2BContainerShipment;
use App\Services\B2BFreightService;
use Illuminate\Console\Command;

class SyncB2BFreightShipmentsCommand extends Command
{
    protected $signature = 'b2b:freight:sync {--queue : Dispatch sync jobs to the queue instead of syncing inline}';

    protected $description = 'Sync active B2B freight container shipments from forwarder providers.';

    public function handle(B2BFreightService $freightService): int
    {
        $shipments = B2BContainerShipment::with('forwarder')
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->get();

        $results = ['total' => $shipments->count(), 'synced' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($shipments as $shipment) {
            if (!$shipment->forwarder || !$shipment->forwarder->is_active) {
                $results['skipped']++;
                continue;
            }

            if ($this->option('queue')) {
                SyncB2BFreightShipmentJob::dispatch($shipment->id);
                $results['synced']++;
                continue;
            }

            $result = $freightService->syncContainerShipment($shipment);
            if ($result['success'] ?? false) {
                $results['synced']++;
            } else {
                $results['failed']++;
            }
        }

        $this->info(sprintf('Freight sync complete. Total: %d, Synced: %d, Skipped: %d, Failed: %d', $results['total'], $results['synced'], $results['skipped'], $results['failed']));

        return self::SUCCESS;
    }
}
