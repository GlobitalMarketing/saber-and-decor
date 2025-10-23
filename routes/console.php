<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:sync-touch365-departments')->daily();
Schedule::command('app:sync-touch365-manufacturers')->daily();
Schedule::command('app:sync-touch365-options')->daily();
Schedule::command('app:sync-touch365-orders')->daily();
Schedule::command('app:sync-touch365-products')->everyMinute();
Schedule::command('app:sync-woo-attributes')->everyMinute();
Schedule::command('app:sync-woo-products')->everyMinute();
// Schedule::command('app:sync-pos-2-woo-orders')->everyMinute();
//
