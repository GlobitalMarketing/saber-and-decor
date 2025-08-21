<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CallTouch365ApiJob;
use App\Models\Installation;

class SyncTouch365Options extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-touch365-options';

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
        $this->info('Dispatching SyncTouch365Options...');
        
        // Dispatch the job (queued version)
        Installation::whereHas('license', function ($query) {
            $query->where('status', 'active');
        })->each(function ($installation) {
            CallTouch365ApiJob::dispatch('/api/option', $installation->id)->onQueue('options');
        });

        $this->info('Job dispatched successfully.');
    }
}
