<?php
namespace Modules\Payment\Managers\Contracts;

use App\Enums\KycStatus;
use Modules\Payment\Managers\Dtos\Bank\CreateCustomerDto;
use Modules\Payment\Managers\Dtos\Bank\AccountDto;
use Modules\Payment\Managers\Dtos\Bank\AccountVerificationDto;
use Modules\Payment\Managers\Dtos\Bank\TransferDto;
use Modules\Payment\Managers\Dtos\Bank\TransferRecipientDto;

interface BankContract
{
    // Customer Management
    public function createCustomer(array $data): CreateCustomerDto;
    public function verifyKyc(CreateCustomerDto $dataObj): KycStatus;

    // Account Management
    public function createDepositAccount(CreateCustomerDto $dataObj): AccountDto;
    public function createAccount(AccountDto $dataObj): AccountDto; // For Anchor's 2-step process

    // Bank Operations
    public function listBanks(string $country = 'NG'): array; // Returns array of BankDto
    public function resolveAccountNumber(string $accountNumber, string $bankCode): AccountVerificationDto;

    // Transfer Management
    public function createTransferRecipient(array $data): TransferRecipientDto;
    public function initiateTransfer(array $data): TransferDto;
    public function verifyTransfer(string $reference): TransferDto;

    // Balance
    public function getBalance(): array; // ['balance' => float, 'currency' => string]

    // Webhook
    public function processWebhook(): void;
}
