<?php
namespace Modules\Payment\Managers\Dtos\Bank;

class BankDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $code,
        public readonly string $slug,
        public readonly string $provider
    ) {}
}
