<?php

use ClarityTech\Shopify\Http\Controllers\ShopifyWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Entries
|--------------------------------------------------------------------------
|
| Handles incoming webhooks for now app uninstalled is automatically handled.
|
*/

    
Route::post('/uninstalled', [ShopifyWebhookController::class, 'handle'])
    ->middleware('verify.webhook')
    ->name('shopify.uninstalled.webhook');
