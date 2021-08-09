# Laravel Shopify

Laravel Shopify is a simple package which helps to build robust integration into Shopify.

## Installation

Add package to composer.json

    composer require clarity-tech/laravel-shopify

### Laravel 5.5+

Package auto discovery will take care of setting up the alias and facade for you


### Laravel 5.4 <

Add the service provider to config/app.php in the providers array.

```php5
<?php

'providers' => [
    ...
    ClarityTech\Shopify\ShopifyServiceProvider::class,
],
```


Setup alias for the Facade

```php5
<?php

'aliases' => [
    ...
    'Shopify' => ClarityTech\Shopify\Facades\Shopify::class,
],
```

## Set credendials

in your `.env` file set these values from your app
`SHOPIFY_APIKEY=your-api-key`
`SHOPIFY_SECRET=your-secret-key`
`SHOPIFY_VERSION=admin-api-version`

## Optional Configuration (publishing)

Laravel Shopify requires api key configuration. You will need to publish configs assets

`php artisan vendor:publish --tag=shopify-config`

This will create a shopify.php file in the config directory. You will need to set your **API_KEY** and **SECRET**
```
'key' => env("SHOPIFY_APIKEY", null),
'secret' => env("SHOPIFY_SECRET", null)
```

## Usage

To install/integrate a shop you will need to initiate an oauth authentication with the shopify API and this require three components.

They are:

    1. Shop URL (eg. example.myshopify.com)
    2. Scope (eg. write_products, read_orders, etc)
    3. Redirect URL (eg. http://mydomain.com/authorize)

This process will enable us to obtain the shops access token

```php
use ClarityTech\Shopify\Facades\Shopify;

Route::get("shop/install", function(\Illuminate\Http\Request $request)
{
    //$redirectUrl = route('shop.authorize');
    $redirectUrl = "http://mydomain.com/shop/authorize";
    $myShopifyDomain = $request->shop;
    $scope = ["write_products","read_orders"];
    $authorizeUrl = Shopify::getAuthorizeUrl($myShopifyDomain, $scopes, $redirectUrl);
    
    return redirect()->to($authorizeUrl);
});
```

Let's retrieve access token

```php5
Route::get("process_oauth_result",function(\Illuminate\Http\Request $request)
{
    $shopifyApi = resolve('shopify');
    $myShopifyDomain = $request->shop;
    $code = $request->code;

    $token = $shopifyApi
        ->setShopDomain($myShopifyDomain)
        ->getAccessToken($code);
        //this gets access token from shopify and set it to the current  instance which will be passed in further api calls


    dd($accessToken);
    //store the access token for future api calls on behalf of the shop    
    
    // redirect to success page or billing etc.
});
```

To make the code less verbose we have added a app uninstalled job which can be subscribed via the app uninstalled webhook from shopify that can be configured automatically from your shop

After installation dispatch this job

```php5
SubscribeAppUninstalledWebhookJob::dispatch($shop);
```
which will subscribe to the `app/uninstalled` webhook
under `/webhooks/shopify/uninstalled` route and will 

To verify request(hmac)

```php5
use ClarityTech\Shopify\Facades\Shopify;

public function verifyRequest(Request $request)
{
    $params = $request->all();

    if (Shopify::verifyRequest($params)){
        logger("verification passed");
    }else{
        logger("verification failed");
    }
}

```

To verify webhook(hmac)

```php5
use ClarityTech\Shopify\Facades\Shopify;

public function verifyWebhook(Request $request)
{
    $data = $request->getContent();
    $hmacHeader = $request->header('x-shopify-hmac-sha256');

    if (Shopify::verifyWebHook($data, $hmacHeader)) {
        logger("verification passed");
    } else {
        logger("verification failed");
    }
}

```

To access API resource use

```php5
Shopify::get("resource uri", ["query string params"]);
Shopify::post("resource uri", ["post body"]);
Shopify::put("resource uri", ["put body"]);
Shopify::delete("resource uri");
```

Let use our access token to get products from shopify.

**NB:** You can use this to access any resource on shopify (be it Product, Shop, Order, etc)

```php5
use ClarityTech\Shopify\Facades\Shopify;

$shopUrl = "example.myshopify.com";
$accessToken = "xxxxxxxxxxxxxxxxxxxxx"; //retrieve from your storage(db)
$products = Shopify::setShop($myShopifyDomain, $accessToken)->get("admin/products.json");
```

To pass query params

```php5
// returns Collection
Shopify::setShop($myShopifyDomain, $accessToken);
$products = Shopify::get('admin/products.json', ["limit"=>20, "page" => 1]);
```

## Controller Example

If you prefer to use dependency injection over facades like me, then you can inject the Class:

```php5
use Illuminate\Http\Request;
use ClarityTech\Shopify\Shopify;

class Foo
{
    protected $shopify;

    public function __construct(Shopify $shopify)
    {
        $this->shopify = $shopify;
    }

    /*
    * returns products
    */
    public function getProducts(Request $request)
    {
        $accessToken = 'xxxxxxxxxxxxxxxxxxxxx';//retrieve from your storage(db)
        $products = $this->shopify->setShop($request->shop, $accessToken)
            ->get('admin/products.json');

        dump($products);
    }
}
```

## Miscellaneous

To get Response headers

```php5
Shopify::getHeaders();
```

To get specific header
```php5
Shopify::getHeader("Content-Type");
```

Check if header exist
```php5
if(Shopify::hasHeader("Content-Type")){
    echo "Yes header exist";
}
```

To get response status code or status message
```php5
Shopify::getStatusCode(); // 200
Shopify::getReasonPhrase(); // ok
```

Optional features

We also have a middleware for verifying the webhooks
you can directly use it in your webhooks by the name `verify.webhook`


We have also added a automatic app uninstalled job dispatch when app is uninstalled by subscribing to the webhook topic `app/uninstalled`.
To configure this you need to implement the interface `Shopify/Contracts/ShopifyShop` in your shop model and then

```php5
SubscribeAppUninstalledWebhookJob::dispatch($shop);
```

To customize the AppUninstalled Job
Publish it by
`php artisan vendor:publish --tag=shopify-jobs`

You might not need this
We also dispatch events for webhooks if it is not for uninstalled topic for the same webhook
`ClarityTech\Shopify\Events\ShopifyWebhookRecieved`





