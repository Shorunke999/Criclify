<?php


namespace Modules\Payment\Repositories\Contracts;


interface PaymentProviderInterface
{
    public function initialize(array $payload): array;

    public function verify(string $reference): array;

    public function webhook();
}
