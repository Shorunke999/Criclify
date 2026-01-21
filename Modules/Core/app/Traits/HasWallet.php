<?php

namespace Modules\Core\Traits;

use Exception;
use Modules\Payment\Enums\TransactionTypeEnum;

trait HasWallet
{

    public function debitWallet(float $amount)
    {
        try{
            $wallet = $this->wallet;
            if($wallet->balance < $amount) throw new Exception('Insufficient Fund in Wallet',422);
            $wallet->decrement('balance', $amount);
        }catch(Exception $e){
            throw $e;
        }
    }
    public function creditWallet(float $amount)
    {
        try{
            $wallet = $this->wallet;
            $wallet->increment('balance', $amount);
        }catch(Exception $e){
            throw $e;
        }
    }
}
