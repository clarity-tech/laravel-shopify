<?php

namespace ClarityTech\Shopify\Api;

class Shop extends Entity
{
   
    /**
     * @param array $params
     *
     * @return ClarityTech\Shopify\Api\Shop
     */
    public function fetch(array $params = [])
    {
        return parent::fetch($params);
    }
}
