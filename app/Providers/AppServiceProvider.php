<?php

namespace App\Providers;

use App\Services\Touch365Api;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Orders\OrderRepository;
use App\Repositories\Options\OptionRepository;
use App\Repositories\Products\ProductRepository;
use App\Repositories\Orders\OrderRepositoryInterface;
use App\Repositories\Departments\DepartmentRepository;
use App\Repositories\Options\OptionRepositoryInterface;
use App\Repositories\Products\ProductRepositoryInterface;
use App\Repositories\Manufacturers\ManufacturerRepository;
use App\Repositories\Departments\DepartmentRepositoryInterface;
use App\Repositories\Manufacturers\ManufacturerRepositoryInterface;

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
        $this->app->singleton(\App\Factories\Touch365ApiFactory::class);
        $this->app->singleton(\App\Factories\WooClientFactory::class);
        $this->app->bind(DepartmentRepositoryInterface::class, DepartmentRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(ManufacturerRepositoryInterface::class, ManufacturerRepository::class);
        $this->app->bind(OptionRepositoryInterface::class, OptionRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
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
