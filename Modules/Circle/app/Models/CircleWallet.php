<?php

namespace Modules\Circle\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Circle\Database\Factories\CircleWalletFactory;

// use Modules\Circle\Database\Factories\CircleWalletFactory;

class CircleWallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
     protected $fillable = [
        'circle_id',
        'balance',
        'currency',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'locked_balance' => 'decimal:2',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function circle()
    {
        return $this->belongsTo(Circle::class);
    }

    protected static function newFactory(): CircleWalletFactory
    {
         return CircleWalletFactory::new();
    }
}
