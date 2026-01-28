<?php
namespace Modules\Payment\Managers\Dtos\Bank;

class TransferDto
{
    public function __construct(
        public readonly string $transferCode,     // Provider's transfer ID
        public readonly float $amount,
        public readonly string $status,           // 'pending', 'success', 'failed'
        public readonly string $reference,
        public readonly string $recipientCode,
        public readonly string $provider,
        public readonly ?string $reason = null,
        public readonly ?array $metadata = null
    ) {}
}
