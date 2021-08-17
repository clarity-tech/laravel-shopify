<?php

namespace ClarityTech\Shopify;

use ClarityTech\Shopify\Facades\Shopify;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::viaRequest('shopify-auth', function (Request $request) {
            $shop = null;
            $user_provider = Auth::createUserProvider('shops');
            $class = $user_provider->getModel();

            if($class){
                $object = new $class();
                
                if($object){
                    if ($request->has('token')) {
                        $token = $request->token;
                        $key = config('shopify.secret');
                        $tokenParts = explode(".", $token);
                        $tokenPayload = base64_decode($tokenParts[1]);
                        $jwtPayload = json_decode($tokenPayload);
        
                        if ($this->verifyJwtToken($token, $key) && $jwtPayload) {
                            $myshopify_domain = Str::replaceFirst('https://', '', $jwtPayload->dest);
                            
                            $shop = $user_provider->retrieveByCredentials([
                                $object->username() => $myshopify_domain
                            ]);
                        }
                    } elseif ($request->has('shop') && $request->has('hmac')) {
                        if (Shopify::verifyRequest($request->all())) {
                            $shop = $user_provider->retrieveByCredentials([
                                $object->username() => $request->shop
                            ]);
                        }
                    }
                }
            }
            return $shop;
        });
    }

    function verifyJwtToken($token, $key)
    {
        $tokenParts = explode(".", $token);
        $data = $tokenParts[0] . "." . $tokenParts[1];
        $hmc = hash_hmac('sha256', $data, $key, true);
        $base64UrlSignature = $this->base64UrlEncode($hmc);
        if ($base64UrlSignature == $tokenParts[2]) {
            return true;
        } else {
            return false;
        }
    }

    function base64UrlEncode($text)
    {
        return str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode($text)
        );
    }
}
