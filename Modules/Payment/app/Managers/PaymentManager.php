<?php
namespace Modules\Payment\Managers;

use Illuminate\Support\Manager;
use Modules\Payment\Integrations\Service\PaystackService;

class PaymentManager extends Manager
{
    public function createPaystackDriver()
    {
        return new PaystackService();
    }
    public function getDefaultDriver()
    {
        return config('app.payment_driver','paystack');
    }

    // public function createXDriver()
    // {
    //     return new XService();
    // }
}
