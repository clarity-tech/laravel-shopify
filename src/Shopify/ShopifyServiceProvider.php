<?php

namespace ClarityTech\Shopify;

use ClarityTech\Shopify\Http\Middleware\VerifyShopifyWebhook;
use Illuminate\Support\Facades\Route;
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

            $this->publishJobs();
        }

        $this->registerRoutes();
        $this->bootMiddlewares();

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
     * Get the Telescope route group configuration array.
     *
     * @return array
     */
    private function routeConfiguration()
    {
        return [
            'namespace' => 'ClarityTech\Shopify\Http\Controllers',
            'prefix' => config('shopify.prefix'),
            'middleware' => 'api',
        ];
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    private function registerRoutes()
    {
        if (! config('shopify.enable_webhook')) {
            return;
        }
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        });
    }

    /**
     * Boot the middlewares for the package.
     *
     * @return void
     */
    private function bootMiddlewares(): void
    {
        // Middlewares
        $this->app['router']->aliasMiddleware('verify.webhook', VerifyShopifyWebhook::class);
    }

    /**
     * Boot the jobs for the package.
     *
     * @return void
     */
    private function publishJobs(): void
    {
        // Job publish
        $this->publishes(
            [
                __DIR__.'/Jobs/AppUninstalledJob.php' => "{$this->app->path()}/Jobs/AppUninstalledJob.php",
            ],
            'shopify-jobs'
        );
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
