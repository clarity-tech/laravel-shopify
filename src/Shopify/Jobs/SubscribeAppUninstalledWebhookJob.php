<?php

namespace ClarityTech\Shopify\Jobs;

use ClarityTech\Shopify\Contracts\ShopifyShop;
use ClarityTech\Shopify\Facades\Shopify;
use ClarityTech\Shopify\Shopify as ShopifyShopify;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Webhook job responsible for handling when the app is uninstalled.
 */
class SubscribeAppUninstalledWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The ShopifyShop shop.
     *
     */
    protected ShopifyShop $shop;

    /**
     * Create a new job instance.
     *
     *
     * @return void
     */
    public function __construct(ShopifyShop $shop)
    {
        $this->shop = $shop;
    }

    /**
     * Execute the job.
     *
     *
     * @return bool
     */
    public function handle(): bool
    {
        if (! config('shopify.enable_webhook')) {
            return true;
        }
        $shopifyApi = $this->getShopifyApi();
        
        $attributes = [
            'topic' => ShopifyShopify::UNINSTALL_TOPIC,
            'address' => route('shopify.uninstalled.webhook'),
            'format' => 'json',
        ];

        $shopifyApi->webhook->create($attributes);

        return true;
    }

    public function getShopifyApi()
    {
        $domain = $this->shop->getShopifyDomain();
        $accessToken = $this->shop->getShopToken();

        $shopifyApi = Shopify::setShop($domain, $accessToken);
        return $shopifyApi;
    }
}
