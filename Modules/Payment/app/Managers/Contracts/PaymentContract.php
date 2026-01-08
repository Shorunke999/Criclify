<?php
namespace Modules\Payment\Managers\Contracts;

interface PaymentContract
{
    public function initialize(array $data):array;
    public function verify(string $reference): array;
    public function webhook();

}
