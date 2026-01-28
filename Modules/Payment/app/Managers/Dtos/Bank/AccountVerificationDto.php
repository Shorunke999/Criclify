<?php
namespace Modules\Payment\Managers\Dtos\Bank;

class AccountVerificationDto
{
    public function __construct(
        public readonly string $accountNumber,
        public readonly string $accountName,
        public readonly string $bankCode,
        public readonly ?int $bankId = null
    ) {}
}
