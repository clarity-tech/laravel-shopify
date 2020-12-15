<?php

namespace ClarityTech\Shopify\Http\Controllers;

use ClarityTech\Shopify\Events\ShopifyWebhookRecieved;
use ClarityTech\Shopify\Shopify;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * Responsible for handling incoming webhook requests.
 */
class ShopifyWebhookController extends Controller
{
    public function handle(Request $request) : Response
    {
        // validation
        $topic = $request->header('x-shopify-topic');
        $shopDomain = $request->header('x-shopify-shop-domain');
        $shopifyId = $request->get('id');

        $payload = $request->all();

        if ($topic == Shopify::UNINSTALL_TOPIC) {
            $jobClass = config('shopify.uninstall.job');
            // AppUninstalledJob::dispatch($shopifyId, $shopDomain, $payload);
            $jobClass::dispatch($shopifyId, $shopDomain, $payload);
        } else {
            ShopifyWebhookRecieved::dispatch($shopDomain, $topic, $payload);
        }

        return new Response('', 200);
    }
}
