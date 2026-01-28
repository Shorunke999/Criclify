<?php

namespace Modules\Payment\Managers\Dtos\Bank;

class CreateCustomerDto
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly ?string $middleName = '',
        public readonly ?string $maidenName = '',
        public readonly string $addressLine1 = '',
        public readonly ?string $addressLine2 = '',
        public readonly string $city = '',
        public readonly string $state = '',
        public readonly string $postalCode = '',
        public readonly string $country = '',
        public readonly string $phoneNumber = '',
        public readonly string $dob = '',
        public readonly string $gender = '',
        public readonly string $bvn = '',
        public readonly string $acctno = '',
        public readonly string $bankCode = '',
        public readonly string $provider ='',
        public readonly string $customerId,
        public readonly bool $kycVerified = false
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public static function fromAnchor(array $attr, array $response = []): self
    {
        return new self(
            firstName: $attr['fullName']['firstName'],
            lastName: $attr['fullName']['lastName'],
            middleName: $attr['fullName']['middleName'] ?? null,
            maidenName: $attr['fullName']['maidenName'] ?? null,
            email: auth()->user()->email,
            addressLine1: $attr['address']['addressLine_1'],
            addressLine2: $attr['address']['addressLine_2'] ?? null,
            city: $attr['address']['city'],
            state: $attr['address']['state'],
            postalCode: $attr['address']['postalCode'],
            country: $attr['address']['country'],
            phoneNumber: $attr['phoneNumber'],
            dob: $attr['identificationLevel2']['dateOfBirth'],
            gender: $attr['identificationLevel2']['gender'],
            bvn: $attr['identificationLevel2']['bvn'],
            provider: 'anchor',
            customerId: $response['data']['id']
        );
    }
    public static function fromPaystack(array $payload, array $response): self
    {
       return new self(
        firstName: $payload['first_name'],
        lastName: $payload['last_name'],
        email: $payload['email'],
        middleName: $payload['middle_name'] ?? '',
        maidenName: '', // Not used in Paystack
        addressLine1: '', // Not used in Paystack
        addressLine2: '',
        city: '',
        state: '',
        postalCode: '',
        country: $payload['country'] ?? 'NG',
        phoneNumber: $payload['phone_number'] ?? '',
        dob: $payload['dob'] ?? '',
        gender: $payload['gender'] ?? '',
        bvn: $payload['bvn'] ?? '',
        acctno: $payload['account_number'] ?? '',
        bankCode: $payload['bank_code'] ?? '',
        provider: 'paystack',
        customerId: $response['data']['customer_code'] ?? '',
        kycVerified: false
    );
    }

    public function toProviderAccountArray(): array
    {
        return [
            'provider_customer_id' => $this->customerId,
            'meta' => [
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'email' => $this->email,
                'phone_number' => $this->phoneNumber,
                'kyc_verified' => $this->kycVerified,
                'bvn' => $this->bvn,
                'dob' => $this->dob,
                'gender' => $this->gender,
                'middle_name' => $this->middleName,
                'address' => $this->addressLine1 ?? $this->addressLine2 ?? '',
            ]
        ];
    }
    public static function fromArray(array $data): self
    {
        return new self(
            firstName: $data['first_name'] ?? $data['firstName'] ?? '',
            lastName: $data['last_name'] ?? $data['lastName'] ?? '',
            email: $data['email'] ?? '',
            phoneNumber: $data['phone_number'] ?? $data['phoneNumber'] ?? '',
            customerId: $data['customerId'] ?? $data['customer_id'] ?? '',
            kycVerified: $data['kyc_verified'] ?? $data['kycVerified'] ?? false,
            middleName: $data['middle_name'] ?? $data['middleName'] ?? null,
            bvn: $data['bvn'] ?? null,
            dob: $data['dob'] ?? null,
            gender: $data['gender'] ?? null,
            addressLine1: $data['address'] ?? null
        );
    }

}
