<?php

use App\Jobs\CallTouch365ApiJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\Touch365Api;
use App\Repositories\Departments\DepartmentRepositoryInterface;
use Illuminate\Support\Facades\App;
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
// Schedule the job
Schedule::job(new CallTouch365ApiJob)->everyMinute();
// Schedule::call(function () {
//     CallTouch365ApiJob::dispatch();
// })->everyMinute();