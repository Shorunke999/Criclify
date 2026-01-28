<?php

namespace Modules\Payment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Models\Wallet;

// use Modules\Payment\Database\Factories\ProviderAccountFactory;

class ProviderAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
     protected $fillable = [
        'user_id',
        'provider',
        'provider_account_id',
        'provider_customer_id',
        'account_number',
        'currency',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function wallet()
    {
        return $this->belongsTo(User::class);
    }

    // protected static function newFactory(): ProviderAccountFactory
    // {
    //     // return ProviderAccountFactory::new();
    // }
}
