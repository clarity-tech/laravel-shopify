<?php

namespace ClarityTech\Shopify\Http\Middleware;

use ClarityTech\Shopify\Facades\Shopify;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

/**
 * Response for ensuring a proper webhook request.
 */
class VerifyShopifyWebhook
{

    /**
     * Handle an incoming request to ensure webhook is valid.
     *
     * @param Request  $request The request object.
     * @param \Closure $next    The next action.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $hmac = $request->header('x-shopify-hmac-sha256') ?: '';
        $data = $request->getContent();

        $result = Shopify::verifyWebHook($data, $hmac);

        if ($result) {
            // All good, process webhook
            return $next($request);
        }

        return Response::make('Invalid webhook signature.', 401);
    }
}
