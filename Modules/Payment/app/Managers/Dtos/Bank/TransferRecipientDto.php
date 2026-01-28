<?php

namespace Modules\Payment\Managers\Dtos\Bank;

class TransferRecipientDto
{
    public function __construct(
        public readonly string $recipientCode,    // Provider's recipient ID
        public readonly string $recipientName,
        public readonly string $accountNumber,
        public readonly string $bankCode,
        public readonly string $bankName,
        public readonly string $provider,
        public readonly ?array $metadata = null
    ) {}
}
