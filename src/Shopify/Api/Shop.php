<?php

namespace ClarityTech\Shopify\Api;

class Shop extends Entity
{
    public static function isSingle() : bool
    {
        return true;
    }
   
    /**
     * @param array $params
     *
     * @return ClarityTech\Shopify\Api\Shop
     */
    public function fetch(array $params = [])
    {
        return parent::fetch($params);
    }

    public function all($options = [])
    {
        return parent::all($options);
    }
}
