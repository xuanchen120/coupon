<?php

namespace XuanChen\Coupon;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{

    /**
     * Register services.
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/xuanchen_coupon.php' => config_path('xuanchen_coupon.php')]);
            $this->publishes([__DIR__ . '/../config/pingan.php' => config_path('pingan.php')]);
        }
    }

    /**
     * Bootstrap services.
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/xuanchen_coupon.php', 'xuanchen_coupon');
        $this->mergeConfigFrom(__DIR__ . '/../config/pingan.php', 'pingan');

    }

}
