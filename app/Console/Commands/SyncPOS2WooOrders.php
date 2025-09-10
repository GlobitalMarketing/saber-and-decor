<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Installation;
use Illuminate\Console\Command;
use App\Jobs\InsertPosOrdersToWoo;

class SyncPOS2WooOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-pos-2-woo-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This cron job sync all the imported orders from pos to the customer website based on installation id';

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
            $orders = Order::where('installation_id', $installation->id)
            ->where('is_important', 0)
            ->get();

            if ($orders->isEmpty()) {
                $this->info("No failed orders found for installation ID: {$installation->id}");
                return;
            }

            foreach ($orders as $order) {
                InsertPosOrdersToWoo::dispatch($order);
                // InsertPosOrdersToWoo::dispatch($order)->onQueue('pos_woo');
            }

            $this->info("Dispatched {$orders->count()} failed orders for retry.");
        });

        $this->info('Job dispatched successfully.');
    }
}
