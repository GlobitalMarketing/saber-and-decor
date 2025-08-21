<?php

namespace App\Jobs;

use App\Services\OrderSyncService;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class PushWooOrderToPOSJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $wooOrder,
        public int $installationId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OrderSyncService $syncService)
    {
        $syncService->pushWooOrderToPOS($this->wooOrder, $this->installationId);
    }
}
