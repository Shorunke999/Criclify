<?php

namespace Modules\Payment\Managers\Dtos\Bank;

class AccountDto
{
    public function __construct(
        public readonly ?string $providerAcctId = null,
        public readonly ?string $providerAcctname = null,
        public readonly ?string $providerAcctNumber = null,
        public readonly string $provider,
        public readonly ?string $bankId = null,
        public readonly ?string $bankName = null,
        public readonly ?string $bankCode = null,
        public readonly string $currency = 'NGN',
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
    public function toProviderAccountArray(): array
    {
        return [
            'provider_account_id' => $this->providerAcctId,
            'account_number' => $this->providerAcctNumber,
            'bank_name' => $this->bankName,
            'bank_code' => $this->bankCode,
            'currency' => $this->currency,
            'meta' => array_merge(
                $this->metadata ?? [],
                [
                    'account_name' => $this->providerAcctname,
                    'bank_id' => $this->bankId,
                ]
            )
        ];
    }

    // Also add a merge method to combine customer and account data
    public function mergeWithCustomer(CreateCustomerDto $customerDto): array
    {
        return array_merge(
            $customerDto->toProviderAccountArray(),
            $this->toProviderAccountArray(),
            [
                'meta' => array_merge(
                    $customerDto->toProviderAccountArray()['meta'] ?? [],
                    $this->toProviderAccountArray()['meta'] ?? []
                )
            ]
        );
    }

}
