<?php

use ClarityTech\Shopify\Jobs\AppUninstalledJob;

return [

    /*
    |--------------------------------------------------------------------------
    | Shopify Api
    |--------------------------------------------------------------------------
    |
    | This file is for setting the credentials for shopify api key and secret.
    |
    */

    'key' => env("SHOPIFY_APIKEY", null),
    'secret' => env("SHOPIFY_SECRET", null),

    'version' => env("SHOPIFY_VERSION", null),

    // the prefix of the webhook url for uninstalled job
    'enable_webhook' => (bool) env('ENABLE_WEBHOOK', 1),
    'prefix' => 'webhooks/shopify',

    // by default will automatically subscribe to app uninstalled topic
    // and dispatch the job of uninstalled
    // to do so dispatch `SubscribeAppUninstalledWebhookJob` when app is installed
    'uninstall' => [
        'job' => AppUninstalledJob::class,
        //job that will be fired upon receiving of the app uninstalled webhook
        //you can extend and override with your own
    ],

    // 'webhooks' => [
    //     [
    //     ],
    // ],
];
