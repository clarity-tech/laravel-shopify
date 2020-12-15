<?php

namespace ClarityTech\Shopify\Api;

class Webhook extends Entity
{
   
    /**
     * Retrieve a single webhook
     * @param array $params
     *
     * @return ClarityTech\Shopify\Api\Webhook
     */
    public function fetch(array $params = [])
    {
        return parent::fetch($params);
    }
    //GET https://shopify.dev/docs/admin-api/rest/reference/events/webhook#show-2020-10

    /**
     * Retrieve a list of webhooks
     * @param array $params
     *
     * @return ClarityTech\Shopify\Api\Webhook
     */
    public function all(array $params = [])
    {
        return parent::all($params);
    }

    /**
     * @param $id Order id description
     */
    public function create(array $attributes = [])
    {
        return parent::create($attributes);
    }
    //https://shopify.dev/docs/admin-api/rest/reference/events/webhook#create-2020-10
}
