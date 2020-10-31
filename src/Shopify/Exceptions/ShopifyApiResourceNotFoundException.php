<?php
/**
 * Created by ClarityTech.
 * User: ClarityTech
 * Date: 9/14/16
 * Time: 7:28 PM
 */

namespace ClarityTech\Shopify\Exceptions;

use Exception;

class ShopifyApiResourceNotFoundException extends Exception
{

    /**
     * ShopifyApiException constructor.
     * @param $message
     * @param int code
     */
    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }
}