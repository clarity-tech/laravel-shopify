<?php

namespace ClarityTech\Shopify\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * A sample stub for handling the App uninstall webhook
 * Webhook job responsible for handling when the app is uninstalled.
 */
class AppUninstalledJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;


    public int $shopifyId;
    /**
     * The shop domain.
     *
     * @var string
     */
    protected $domain;

    /**
     * The webhook data.
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new job instance.
     *
     * @param string   $domain The shop domain.
     * @param array $data The webhook data (JSON decoded).
     *
     * @return void
     */
    public function __construct(int $shopifyId, string $shopDomain, array $data)
    {
        $this->shopifyId = $shopifyId;
        $this->domain = $shopDomain;
        $this->data = $data;
    }

    /*
     * @return bool
     */
    public function handle(): bool
    {
        // Get the shop by the shopifyid

        // prevalidate anything required

        // Cancel the current plan

        $this->beforeUninstall();

        // delete the shop or mark as inactive

        $this->afterUninstall();

        return true;
    }

    public function beforeUninstall()
    {
        # code...
    }

    public function afterUninstall()
    {
        # code...
    }
}
