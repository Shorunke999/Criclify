<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Payment\Database\Factories\TransactableFactory;

class Transactable extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'transactable_id',
        'transactable_type',
        'amount'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function transactable()
    {
        return $this->morphTo();
    }

    // protected static function newFactory(): TransactableFactory
    // {
    //     // return TransactableFactory::new();
    // }
}
