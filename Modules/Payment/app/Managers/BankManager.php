<?php
namespace Modules\Payment\Managers;

use Illuminate\Support\Manager;
use Modules\Payment\Integrations\Service\AnchorService;
use Modules\Payment\Integrations\Service\PaystackService;

class BankManager extends Manager
{
    public function getDefaultDriver()
    {
        return config('app.bank_driver','anchor');
    }
    public function createAnchorDriver()
    {
        return app(AnchorService::class);
    }
    public function createPaystackDriver()
    {
        return app(PaystackService::class);
    }
    // public function createXDriver()
    // {
    //     return new XService();
    // }
}
