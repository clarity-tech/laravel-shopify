<?php

namespace ClarityTech\Shopify;

use Illuminate\Support\ServiceProvider;

class ShopifyServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/shopify.php' => config_path('shopify.php'),
            ], 'shopify-config');
        }
        

        $this->app->alias('Shopify', 'ClarityTech\Shopify\Facades\Shopify');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/shopify.php', 'shopify');

        $this->app->singleton('shopify', function ($app) {
            return new Shopify();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
