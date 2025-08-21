<?php

namespace App\Console\Commands;

use App\Models\Installation;
use App\Jobs\SyncProductsJob;
use Illuminate\Console\Command;

class SyncWooProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-woo-products';

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
        $this->info('Dispatching SyncWooProducts...');
        
        // Dispatch the job (queued version)
        Installation::whereHas('license', function ($query) {
            $query->where('status', 'active');
        })->each(function ($installation) {
            SyncProductsJob::dispatch($installation->id)->onQueue('woo_products'. $installation->id);
        });

        $this->info('Job dispatched successfully.');
    }
}
