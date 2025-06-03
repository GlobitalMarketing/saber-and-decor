<?php

namespace App\Providers;

use App\Repositories\Departments\DepartmentRepository;
use App\Repositories\Departments\DepartmentRepositoryInterface;
use App\Services\Touch365Api;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /**
         * creating singleton for Touch365Api
         */
        // $this->app->singleton(Touch365Api::class, function ($app) {
        //     return new Touch365Api(
        //         env('TOUCH365_USERNAME'),
        //         env('TOUCH365_PASSWORD'),
        //         env('TOUCH365_TENANT'),
        //         env('TOUCH365_URL')
        //     );
        // });
        $this->app->singleton(Touch365Api::class, function ($app) {
            return new Touch365Api();  // You can pass any dependencies if needed
        });
        $this->app->bind(DepartmentRepositoryInterface::class, DepartmentRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (env('APP_ENV') !== 'production') {
            Schema::defaultStringLength(191);
        }
    }
}
