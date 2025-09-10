<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Installation;
use Illuminate\Console\Command;
use App\Jobs\CallTouch365ApiJob;
use Illuminate\Support\Facades\Log;

class SyncTouch365Products extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-touch365-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Dispatching SyncTouch365Products...');
        Log::info('SyncTouch365Products command started.');

        // Dispatch the job (queued version)
        Installation::whereHas('license', function ($query) {
            $query->where('status', 'active');
        })->each(function ($installation) {
            $queueName = 'products_' . $installation->id;
            $payload = [
                'last_updated' => Carbon::now()->format('Y-m-d')
            ];
            Log::info("Dispatching CallTouch365ApiJob for installation ID: {$installation->id} on queue: {$queueName}", $payload);
            CallTouch365ApiJob::dispatch('/api/product/option', $installation->id, $payload);
            // CallTouch365ApiJob::dispatch('/api/product/option', $installation->id, $payload)
            //     ->onQueue('products');

        });

        $this->info('Job dispatched successfully.');
        Log::info('SyncTouch365Products command finished.');
    }
}
