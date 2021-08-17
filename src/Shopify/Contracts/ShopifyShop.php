<?php

namespace ClarityTech\Shopify\Contracts;

interface ShopifyShop
{
    public function getShopifyId() : int;

    public function getShopToken() : string;

    public function getShopifyDomain() : string;

    public function username() : string;
}
