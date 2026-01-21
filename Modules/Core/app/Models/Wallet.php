<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Enums\WalletTypeEnum;
use Modules\Circle\Models\Circle;
use App\Models\User;
use Illuminate\Support\Str;
use Modules\Payment\Models\Transaction;


class Wallet extends Model
{
    use HasFactory;

     protected $fillable = [
        'walletable_type',
        'walletable_id',
        'currency_id',
        'balance',
        'wallet_number',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
     protected static function booted()
    {
        static::creating(function ($wallet) {
            if (!$wallet->wallet_number) {
                $wallet->wallet_number = self::generateWalletNumber();
            }
        });
    }

    public function walletable()
    {
        return $this->morphTo();
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    private static function generateWalletNumber(): string
    {
        do {
            $number = 'WLT' . strtoupper(Str::random(10));
        } while (self::where('wallet_number', $number)->exists());

        return $number;
    }

    public function circle()
    {
        return $this->belongsTo(Circle::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
