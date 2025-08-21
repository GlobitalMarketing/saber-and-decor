<?php

namespace App\Services\WooSync;

use Illuminate\Support\Facades\Log;

class LogService
{
    public function write(string $message): void
    {
        $timestamp = now()->format('H:i:s A');
        file_put_contents(
            storage_path('logs/woo_sync_' . now()->toDateString() . '.log'),
            "$message $timestamp\n",
            FILE_APPEND
        );
    }
}
