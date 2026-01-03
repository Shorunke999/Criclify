<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Payment\Database\Factories\TransactionFactory;
use Modules\Payment\Enums\TransactionStatusEnum;
use Modules\Payment\Enums\TransactionTypeEnum;

// use Modules\Payment\Database\Factories\TransactionFactory;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
     protected $fillable = [
        'user_id',
        'circle_id',
        'type_ids',
        'reference',
        'amount',
        'currency',
        'type',
        'status',
        'meta'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'provider_payload' => 'array',
        'status' => TransactionStatusEnum::class,
        'type' => TransactionTypeEnum::class,
        'type_ids' => 'array',
        'meta' => 'array',
    ];

    protected static function newFactory(): TransactionFactory
    {
         return TransactionFactory::new();
    }
}
