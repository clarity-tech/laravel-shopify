<?php

namespace ClarityTech\Shopify;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use ClarityTech\Shopify\Exceptions\ShopifyApiException;
use ClarityTech\Shopify\Exceptions\ShopifyApiResourceNotFoundException;
use Psr\Http\Message\ResponseInterface;

class Shopify
{
    protected static ?string $key = null;
    protected static ?string $secret = null;
    protected static ?string $shopDomain = null;
    protected static ?string $accessToken = null;

    public const VERSION = '2020-01';
    public const PREFIX = 'admin/api/';

    public bool $debug = false;
    
    protected array $requestHeaders = [];
    protected array $responseHeaders = [];
    protected $responseStatusCode;
    protected $reasonPhrase;

    // public function __construct(Client $client)
    // {
    //     $this->client = $client;
    //     self::$key = Config::get('shopify.key');
    //     self::$secret = Config::get('shopify.secret');
    // }
    public function __construct()
    {
        self::$key = Config::get('shopify.key');
        self::$secret = Config::get('shopify.secret');
    }

    //use Illuminate\Support\Facades\Http;

    public function api()
    {
        return $this;
    }

    /*
     * Set Shop  Url;
     */
    public function setShopDomain(string $shopUrl)
    {
        $url = parse_url($shopUrl);
        self::$shopDomain = isset($url['host']) ? $url['host'] : self::removeProtocol($shopUrl);

        return $this;
    }

    public static function removeProtocol(string $url) : string
    {
        $disallowed = ['http://', 'https://','http//','ftp://','ftps://'];
        foreach ($disallowed as $d) {
            if (strpos($url, $d) === 0) {
                return str_replace($d, '', $url);
            }
        }

        return $url;
    }

    public static function getBaseUrl() : string
    {
        return 'https://' . self::$shopDomain .'/';
    }

    public static function getKey()
    {
        return self::$key;
    }

    public static function getSecret()
    {
        return self::$secret;
    }

    public function version() : string
    {
        return self::VERSION;
    }

    public static function apiPrefix() : string
    {
        return self::PREFIX;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $className = __NAMESPACE__.'\\Api\\'.ucwords($name);

        $entity = new $className();

        return $entity;
    }

    // Get the URL required to request authorization
    public function getAuthorizeUrl(string $url, array $scope = [], $redirect_url='', $nonce='') : string
    {
        $this->setShopDomain($url);

        $scope = implode(",", $scope);
        
        $url = "https://{$this::$shopDomain}/admin/oauth/authorize?client_id={$this::getKey()}&scope=" . urlencode($scope);
        
        if ($redirect_url != '') {
            $url .= "&redirect_uri=" . urlencode($redirect_url);
        }

        if ($nonce!='') {
            $url .= "&state=" . urlencode($nonce);
        }
        
        return $url;
    }

    /**
     * “exchange” your access code with the shop’s permanent API token:
     * and sets to the current instance
     */
    public function getAccessToken(string $code)
    {
        $uri = 'admin/oauth/access_token';

        $payload = [
            'client_id' => self::getKey(),
            'client_secret' => self::getSecret(),
            'code' => $code
        ];

        $response = $this->makeRequest('POST', $uri, $payload);

        $response = $response->json();

        $accessToken = $response['access_token'] ?? null;

        $this->setAccessToken($accessToken);

        return $accessToken ?? '';
    }

    public function setAccessToken($accessToken)
    {
        self::$accessToken = $accessToken;
        return $this;
    }

    public function setShop(string $domain, string $accessToken)
    {
        $this->setShopDomain($domain)
            ->setAccessToken($accessToken);

        return $this;
    }

    public function isTokenSet() : bool
    {
        return !is_null(self::$accessToken);
    }

    protected function getXShopifyAccessTokenHeader() : array
    {
        return ['X-Shopify-Access-Token' => self::$accessToken];
    }

    public function addHeader($key, $value) : self
    {
        $this->requestHeaders = array_merge($this->requestHeaders, [$key => $value]);

        return $this;
    }

    public function removeHeaders() : self
    {
        $this->requestHeaders = [];

        return $this;
    }

    public function setDebug(bool $status = true)
    {
        $this->debug = $status;

        return $this;
    }

    /*
     *  $args[0] is for route uri and $args[1] is either request body or query strings
     */
    public function __call($method, $args)
    {
        list($uri, $params) = [ltrim($args[0], '/'), $args[1] ?? []];
        $response = $this->makeRequest($method, $uri, $params);

        if (is_array($array = $response->json()) && count($array) == 1) {
            return array_shift($array);
        }

        return $response;
    }

    public function getHeadersForSend() : array
    {
        $headers = [];

        if ($this->isTokenSet()) {
            $headers = $this->getXShopifyAccessTokenHeader();
        }
        return array_merge($headers, $this->requestHeaders);
    }

    public function makeRequest(string $method, string $path, array $params = [])
    {
        //TODO apply ratelimit or handle it outside from caller function
        // aso that we can have more control when we can retry etc

        $url = self::getBaseUrl() . $path;

        $method = strtolower($method);

        $response = Http::withOptions(['debug' => $this->debug,])
                ->withHeaders($this->getHeadersForSend())
                ->$method($url, $params);

        $this->parseResponse($response->toPsrResponse());

        if ($response->successful()) {
            return $response;
        }

        return $this->throwErrors($response);
    }

    protected function parseResponse(ResponseInterface $response)
    {
        $this
            ->setResponseHeaders($response->getHeaders())
            ->setStatusCode($response->getStatusCode())
            ->setReasonPhrase($response->getReasonPhrase());
    }

    protected function throwErrors($httpResponse)
    {
        $response = $httpResponse->json();
        $psrResponse = $httpResponse->toPsrResponse();

        $statusCode = $psrResponse->getStatusCode();

        if (isset($response['errors']) || $statusCode >= 400) {
            $errorString = null;
            
            if (!is_null($response)) {
                $errorString = is_array($response['errors']) ? json_encode($response['errors']) : $response['errors'];
            }
            
            if ($statusCode  == 404) {
                throw new ShopifyApiResourceNotFoundException(
                    $errorString ?? $psrResponse->getReasonPhrase(),
                    $statusCode
                );
            }
            
            throw new ShopifyApiException(
                $errorString ?? $psrResponse->getReasonPhrase(),
                $statusCode
            );
        }
    }

    public function verifyRequest($queryParams)
    {
        if (is_string($queryParams)) {
            $data = [];

            $queryParams = explode('&', $queryParams);
            foreach ($queryParams as $queryParam) {
                list($key, $value) = explode('=', $queryParam);
                $data[$key] = urldecode($value);
            }

            $queryParams = $data;
        }

        $hmac = $queryParams['hmac'] ?? '';

        unset($queryParams['signature'], $queryParams['hmac']);

        ksort($queryParams);

        $params = collect($queryParams)->map(function ($value, $key) {
            $key   = strtr($key, ['&' => '%26', '%' => '%25', '=' => '%3D']);
            $value = strtr($value, ['&' => '%26', '%' => '%25']);

            return $key . '=' . $value;
        })->implode("&");

        $calculatedHmac = hash_hmac('sha256', $params, self::getSecret());

        return hash_equals($hmac, $calculatedHmac);
    }

    public function verifyWebHook($data, $hmacHeader) : bool
    {
        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, self::getSecret(), true));

        return ($hmacHeader == $calculatedHmac);
    }

    private function setStatusCode($code)
    {
        $this->responseStatusCode = $code;
        return $this;
    }

    public function getStatusCode()
    {
        return $this->responseStatusCode;
    }

    private function setReasonPhrase($message)
    {
        $this->reasonPhrase = $message;
    }

    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    private function setResponseHeaders($headers)
    {
        $this->responseHeaders = $headers;
        return $this;
    }

    public function getHeaders()
    {
        return $this->responseHeaders;
    }

    public function getHeader($header)
    {
        return $this->hasHeader($header) ? $this->responseHeaders[$header] : '';
    }

    public function hasHeader($header)
    {
        return array_key_exists($header, $this->responseHeaders);
    }
}
