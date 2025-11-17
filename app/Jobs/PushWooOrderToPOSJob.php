<?php

namespace App\Jobs;

use App\Services\OrderSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushWooOrderToPOSJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $wooOrder;
    public int $installationId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $wooOrder, int $installationId)
    {
        $this->wooOrder = $wooOrder;
        $this->installationId = $installationId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Job start log
        Log::info("🟢 PushWooOrderToPOSJob STARTED", [
            'order_id' => $this->wooOrder['id'] ?? null,
            'installation_id' => $this->installationId
        ]);

        try {
            // Instantiate the service manually (avoid DI inside queued job)
            $service = new OrderSyncService();

            Log::info("🟢 OrderSyncService instance created");

            // Call the service to push order to POS
            $service->pushWooOrderToPOS($this->wooOrder, $this->installationId);

            Log::info("🟢 PushWooOrderToPOSJob FINISHED");

        } catch (\Throwable $e) {
            // Catch all errors to ensure logs
            Log::error("🔴 PushWooOrderToPOSJob FAILED", [
                'order_id' => $this->wooOrder['id'] ?? null,
                'installation_id' => $this->installationId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
