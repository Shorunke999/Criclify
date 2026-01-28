<?php
namespace Modules\Payment\Integrations\Service;

use App\Enums\KycStatus;
use Exception;
use Illuminate\Http\Client\Request;
use Modules\Payment\Integrations\Clients\AnchorClient;
use Modules\Payment\Managers\Contracts\BankContract;
use Modules\Payment\Managers\Dtos\Bank\AccountDto;
use Modules\Payment\Managers\Dtos\Bank\AccountVerificationDto;
use Modules\Payment\Managers\Dtos\Bank\CreateCustomerDto;
use Modules\Payment\Managers\Dtos\Bank\TransferDto;
use Modules\Payment\Managers\Dtos\Bank\TransferRecipientDto;
use Modules\Payment\Services\PaymentService;

class AnchorService implements BankContract
{
    protected $client;
    protected $transactionService;
    public function __construct(
    ) {
        $this->client = new AnchorClient();
        $this->transactionService = new PaymentService();
    }

    public function createCustomer(array $data): CreateCustomerDto
    {
        $user = auth()->user();

        $payload = [
            'data' => [
                'type' => 'IndividualCustomer',
                'attributes' => [
                    'fullName' => [
                        'firstName'  => $data['first_name'] ?? $user->first_name,
                        'lastName'   => $data['last_name'] ?? $user->last_name,
                        'middleName' => $data['middle_name'] ?? null,
                        'maidenName' => $data['maiden_name'] ?? null,
                    ],
                    'address' => [
                        'addressLine_1' => $data['address_line1'],
                        'addressLine_2' => $data['address_line2'] ?? null,
                        'city'          => $data['city'],
                        'state'         => $data['state'],
                        'postalCode'    => $data['postal_code'],
                        'country'       => $data['country'],
                    ],
                    'email' => $data['email'],
                    'phoneNumber' => $data['phone_number'],
                    'identificationLevel2' => [
                        'dateOfBirth' => $data['dob'],
                        'gender'      => $data['gender'],
                        'bvn'         => $data['bvn'],
                    ],
                    'metadata' => [
                        'user_id' => $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->client->createCustomer($payload);

        if (! $response->successful()) {
            $error = data_get($response->json(), 'errors.0');

            throw new Exception(
                $error['detail'] ?? 'Unable to create customer',
                (int) ($error['status'] ?? $response->status())
            );
        }

        $attributes = $payload['data']['attributes'];

        return CreateCustomerDto::fromAnchor(
            $attributes,
            $response->json()
        );

    }

    public function verifyKyc(CreateCustomerDto $dataObj): KycStatus
    {
        $data =  $dataObj->toArray();
        $payload = [
            'data' => [
                "type"=> "Verification",
                "attributes"=> [
                "level"=> "TIER_2",
                "level2"=> [
                    "bvn"=> $data['bvn'],
                    "dateOfBirth"=> $data['dob'],
                    "gender"=> $data['gender']
                    ]
                ]
            ]
        ];

        $response = $this->client->verifyKyc($payload);
        if (! $response->successful()) {
            $error = data_get($response->json(), 'errors.0');

            throw new Exception(
                $error['detail'] ?? 'Unable to Verify',
                (int) ($error['status'] ?? $response->status())
            );
        }

        return KycStatus::Awaiting;

    }

    public function createDepositAccount(CreateCustomerDto $dataObj):AccountDto
    {
        $data =  $dataObj->toArray();
        $payload = [
            'data' => [
                "type"=> "DepositAccount",
                "attributes"=> [
                     "productName" =>  "SAVINGS"
                ],
                 "relationships"=> [
                    "customer"=> [
                        "data"=> [
                        "id"=> $data['id'],
                        "type"=> "IndividualCustomer"
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->client->createDepositAccount($payload);
        if (! $response->successful()) {
            $error = data_get($response->json(), 'errors.0');

            throw new Exception(
                $error['detail'] ?? 'Unable to Verify',
                (int) ($error['status'] ?? $response->status())
            );
        }
        $responseJson = $response->json();
        $attr = $responseJson['data']['attributes'];
        return new AccountDto(
            providerAcctId: $responseJson['data']['id'],
            provider: 'anchor',
            bankId: $attr['bank']['id'],
            bankName: $attr['bank']['name'],
            bankCode: $attr['bank']['code'],
        );
    }

    public function createAccount(AccountDto $dataObj):AccountDto
    {
        $data =  $dataObj->toArray();
        $payload = [
            'AccountId' => $data['providerAcctId']
        ];
        $response = $this->client->createAccount($payload);
        if (! $response->successful()) {
            $error = data_get($response->json(), 'errors.0');

            throw new Exception(
                $error['detail'] ?? 'Unable to Create Account',
                (int) ($error['status'] ?? $response->status())
            );
        }
        return new AccountDto(
            providerAcctId: $data['providerAcctId'],
            provider: 'anchor',
            bankId: $response['data']['attributes']['bank']['id'],
            bankName: $response['data']['attributes']['bank']['name'],
            bankCode: $response['data']['attributes']['bank']['code'],
            providerAcctNumber: $response['data']['attributes']['accountNumber'],
        );
    }

    public function processWebhook(): void
    {
        $request = request();
        if (! $this->client->verifyWebhookSignature($request)) {
            return;
        }

        $payload = $request->json()->all();
        $type = $payload['type'] ?? '';

        if (str_starts_with($type, 'customer.identification')) {
            $this->handleKycWebhook($payload);
            return;
        }

        if (str_starts_with($type, 'nip.inbound')) {
            $this->handleInboundTransferWebhook($payload);
            return;
        }
    }

    public function handleKycWebhook(Request $request)
    {
    }
    protected function handleInboundTransferWebhook(array $payload): void
    {
        $event = $payload['type'];

        $transferId = data_get($payload, 'relationships.transfer.data.id');
        $accountId  = data_get($payload, 'relationships.account.data.id');

        if ($event === 'nip.inbound.received') {
            $this->transactionService->createTransaction(
                reference: $transferId
            );
        }

        if ($event === 'nip.inbound.completed') {
            $transfer = $this->client->getInboundTransfer($transferId);

            if (! $transfer->successful()) {
                throw new Exception('Unable to fetch inbound transfer');
            }

            $data = $transfer->json()['data']['attributes'];

            // Example:
            // $amount = $data['amount'];
            // $reference = $data['reference'];

            $this->creditWallet(
                accountId: $accountId,
                amount: $data['amount'],
                reference: $data['reference']
            );
        }
    }

    // These methods should throw "Not supported" or implement Anchor equivalents

    public function listBanks(string $country = 'NG'): array
    {
        // Anchor might not have this endpoint, or you return empty array
        // OR implement if Anchor has bank listing
        throw new Exception('Bank listing not supported by Anchor');
    }

    public function resolveAccountNumber(string $accountNumber, string $bankCode): AccountVerificationDto
    {
        // Use Anchor's name enquiry endpoint if available
        throw new Exception('Account resolution not yet implemented for Anchor');
    }

    public function createTransferRecipient(array $data): TransferRecipientDto
    {
        // Anchor might call this "CounterParty"
        // Implement based on Anchor's transfer recipient creation
        throw new Exception('Transfer recipient creation not yet implemented for Anchor');
    }

    public function initiateTransfer(array $data): TransferDto
    {
        // Implement Anchor's NIP transfer endpoint
        throw new Exception('Transfer initiation not yet implemented for Anchor');
    }

    public function verifyTransfer(string $reference): TransferDto
    {
        throw new Exception('Transfer verification not yet implemented for Anchor');
    }

    public function getBalance(): array
    {
        // Fetch your Anchor account balance
        throw new Exception('Balance fetching not yet implemented for Anchor');
    }


}
