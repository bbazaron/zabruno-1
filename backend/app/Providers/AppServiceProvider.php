<?php

namespace App\Providers;

use App\Services\YooKassaClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(YooKassaClient::class, function () {
            return new YooKassaClient(
                config('services.yookassa.shop_id'),
                config('services.yookassa.secret_key'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
