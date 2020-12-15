<?php

namespace ClarityTech\Shopify\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShopifyWebhookRecieved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    /**
     * The myshoipify domain.
     *
     */
    public string $shopDomain;

    /**
     * The shopify webhook topic.
     *
     */
    public string $topic;
    
    /**
     * The webhook payload.
     *
     */
    public array $payload = [];

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $shopDomain, string $topic, array $payload)
    {
        $this->shopDomain = $shopDomain;
        $this->topic = $topic;
        $this->payload = $payload;
    }
}
