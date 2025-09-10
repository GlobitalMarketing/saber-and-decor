<?php

namespace App\Console\Commands;

use App\Models\Installation;
use App\Jobs\SyncAttributesJob;
use Illuminate\Console\Command;

class SyncWooAttributes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-woo-attributes';

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
        $this->info('Dispatching SyncWooAttributes...');
        
        // Dispatch the job (queued version)
        Installation::whereHas('license', function ($query) {
            $query->where('status', 'active');
        })->each(function ($installation) {
            $this->info("Syncing WooCommerce terms for Installation ID: $installation->id");
            SyncAttributesJob::dispatch($installation->id);
            // SyncAttributesJob::dispatch($installation->id)->onQueue('woo_attr_'. $installation->id);
        });

        $this->info('Job dispatched successfully.');
    }
}
