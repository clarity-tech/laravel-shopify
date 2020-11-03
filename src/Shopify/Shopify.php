<?php

namespace ClarityTech\Shopify;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use ClarityTech\Shopify\Exceptions\ShopifyApiException;
use ClarityTech\Shopify\Exceptions\ShopifyApiResourceNotFoundException;
use Illuminate\Support\Collection;

class Shopify
{
    protected Client $client;
    
    protected static ?string $key = null;
    protected static ?string $secret = null;
    protected static ?string $shopDomain = null;
    protected static ?string $accessToken = null;

    public const VERSION = '2020-01';
    public const PREFIX = 'admin/api/';

    // protected ?string $shopDomain;
    
    protected array $requestHeaders = [];
    protected array $responseHeaders = [];
    // protected $responseStatusCode;
    // protected $reasonPhrase;

    public function __construct(Client $client)
    {
        $this->client = $client;
        self::$key = Config::get('shopify.key');
        self::$secret = Config::get('shopify.secret');
    }

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

    protected static function getSecret()
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
            'client_id' => $this::getKey(),
            'client_secret' => $this::getSecret(),
            'code' => $code
        ];

        $response = $this->makeRequest('POST', $uri, $payload);

        $this->setAccessToken($response);

        return $response ?? '';
    }

    public function setAccessToken($accessToken)
    {
        self::$accessToken = $accessToken;
        return $this;
    }

    protected function getXShopifyAccessToken() : array
    {
        return ['X-Shopify-Access-Token' => self::$accessToken];
    }

    public function withAccessToken()
    {
        $accessTokenHeader = $this->getXShopifyAccessToken();

        foreach ($accessTokenHeader as $key => $value) {
            $this->addHeader($key, $value);
        }
        
        
        return $this;
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

    /*
     *  $args[0] is for route uri and $args[1] is either request body or query strings
     */
    public function __call($method, $args)
    {
        list($uri, $params) = [ltrim($args[0], "/"), $args[1] ?? []];
        $response = $this->makeRequest($method, $uri, $params, $this->getXShopifyAccessToken());

        //return (is_array($response)) ? $this->convertResponseToCollection($response) : $response;
        return $response;
    }

    // private function convertResponseToCollection($response) : Collection
    // {
    //     return collect(json_decode(json_encode($response)));
    // }
    

    public function makeRequest(string $method, string $path, array $params = [], array $headers = [])
    {
        $query = in_array($method, ['get','delete']) ? 'query' : 'json';

        $rateLimit = explode('/', $this->getHeader('X-Shopify-Shop-Api-Call-Limit'));

        if ($rateLimit[0] >= 38) {
            sleep(15);
        }

        $url = self::getBaseUrl() . $path;

        $response = $this->client
            ->request(strtoupper($method), $url, [
                'headers' => array_merge($headers, $this->requestHeaders),
                $query => $params,
                'timeout' => 120.0,
                'connect_timeout' => 120.0,
                'http_errors' => false,
                "verify" => false
            ]);

        $this->parseResponse($response);
        $responseBody = $this->responseBody($response);

        if (isset($responseBody['errors']) || $response->getStatusCode() >= 400) {
            if (! is_null($responseBody)) {
                $errors = is_array($responseBody['errors'])
                ? json_encode($responseBody['errors'])
                : $responseBody['errors'];

                if ($response->getStatusCode()  == 404) {
                    throw new ShopifyApiResourceNotFoundException(
                        $errors ?? $response->getReasonPhrase(),
                        $response->getStatusCode()
                    );
                }
            }

            throw new ShopifyApiException(
                $errors ?? $response->getReasonPhrase(),
                $response->getStatusCode()
            );
        }

        return (is_array($responseBody) && (count($responseBody) > 0)) ? array_shift($responseBody) : $responseBody;
    }

    private function parseResponse($response)
    {
        $this->parseHeaders($response->getHeaders());
        $this->setStatusCode($response->getStatusCode());
        $this->setReasonPhrase($response->getReasonPhrase());
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

    private function parseHeaders($headers)
    {
        foreach ($headers as $name => $values) {
            $this->responseHeaders = array_merge($this->responseHeaders, [$name => implode(', ', $values)]);
        }
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

    private function responseBody($response)
    {
        return json_decode($response->getBody(), true);
    }
}
