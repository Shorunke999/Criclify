<?php

namespace Modules\Core\Services;

use App\Traits\ResponseTrait;
use  Modules\Core\Repositories\Contracts\WalletRepositoryInterface;
use Exception;
class WalletService
{
    use ResponseTrait;

    public function __construct(
        protected WalletRepositoryInterface $walletRepository
    ) {}

    public function debitWallet(int $id, float $amount, $type)
    {
        try{
            $wallet = $this->walletRepository->wallet($id,$amount, $type);
            if($wallet->balance < $amount) throw new Exception('Insufficient Fund in Wallet',422);
            $wallet->decrement('balance', $amount);
        }catch(Exception $e){
            throw $e;
        }
    }
    public function creditWallet(int $id, float $amount, $type)
    {
        try{
            $wallet = $this->walletRepository->wallet($id,$amount, $type);
            $wallet->increment('balance', $amount);
        }catch(Exception $e){
            throw $e;
        }
    }
}
