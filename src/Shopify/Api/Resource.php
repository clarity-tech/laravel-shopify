<?php

namespace ClarityTech\Shopify\Api;

use ClarityTech\Shopify\Facades\Shopify;
use Illuminate\Support\Str;

class Resource
{
    // implements ArrayAccess, IteratorAggregate
    protected array $attributes = [];

    // public function getIterator()
    // {
    //     return new ArrayIterator($this->attributes);
    // }

    // public function offsetExists($offset)
    // {
    //     return (isset($this->attributes[$offset]));
    // }

    // public function offsetSet($offset, $value)
    // {
    //     $this->attributes[$offset] = $value;
    // }

    // public function offsetGet($offset)
    // {
    //     return $this->attributes[$offset];
    // }

    // public function offsetUnset($offset)
    // {
    //     unset($this->attributes[$offset]);
    // }
    

    public function __get($key)
    {
        return $this->attributes[$key];
    }

    public function __set($key, $value)
    {
        return $this->attributes[$key] = $value;
    }

    public function setShop($domain, string $accessToken)
    {
        Shopify::setShopUrl($domain)
            ->setAccessToken($accessToken);

        return $this;
    }


    // public function __isset($key)
    // {
    //     return (isset($this->attributes[$key]));
    // }

    // public function __unset($key)
    // {
    //     unset($this->attributes[$key]);
    // }
}
