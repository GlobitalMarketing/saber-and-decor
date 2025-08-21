<?php

namespace App\Console\Commands;

use App\Models\Installation;
use Illuminate\Console\Command;
use App\Jobs\CallTouch365ApiJob;

class SyncTouch365Departments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-touch365-departments';

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
        $this->info('Dispatching SyncTouch365Departments...');
        
        Installation::whereHas('license', function ($query) {
            $query->where('status', 'active');
        })->each(function ($installation) {
            CallTouch365ApiJob::dispatch('/api/department', $installation->id)->onQueue('departments_'. $installation->id);
        });

        $this->info('Job dispatched successfully.');
    }
}
