<?php

namespace App\Jobs;

use App\Services\SyncService;
use App\Services\WooSync\AttributeSyncService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncAttributesJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected int $installationId;

    public function __construct(int $installationId)
    {
        $this->installationId = $installationId;
    }

    /**
     * Execute the job.
     */
    public function handle(AttributeSyncService $syncService): void
    {
        $syncService->syncMissingTerms($this->installationId);
    }
}
