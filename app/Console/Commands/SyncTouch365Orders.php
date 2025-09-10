<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CallTouch365ApiJob;
use App\Models\Installation;

class SyncTouch365Orders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-touch365-orders';

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
        $this->info('Dispatching SyncTouch365Orders...');
        
        // Dispatch the job (queued version)
        Installation::whereHas('license', function ($query) {
            $query->where('status', 'active');
        })->each(function ($installation) {
            CallTouch365ApiJob::dispatch('/api/order', $installation->id);
            // CallTouch365ApiJob::dispatch('/api/order', $installation->id)->onQueue('orders');
        });

        $this->info('Job dispatched successfully.');
    }
}
