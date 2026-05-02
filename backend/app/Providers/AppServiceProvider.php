<?php

namespace App\Providers;

use App\Services\OrderReceivedMailService;
use App\Services\YooKassaClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(YooKassaClient::class, function ($app) {
            return new YooKassaClient(
                config('services.yookassa.shop_id'),
                config('services.yookassa.secret_key'),
                $app->make(OrderReceivedMailService::class),
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
