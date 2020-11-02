<?php

namespace ClarityTech\Shopify\Api;

class CarrierService extends Entity
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
    //GET /admin/api/unstable/carrier_services.json

    /**
     * @param array $params
     *
     * @return ClarityTech\Shopify\Api\CarrierService
     */
    public function all(array $params = [])
    {
        return parent::all($params);
    }
    //GET /admin/api/unstable/carrier_services.json

    /**
     * @param $id Order id description
     */
    public function create(array $attributes = [])
    {
        return parent::create($attributes);
    }
}
