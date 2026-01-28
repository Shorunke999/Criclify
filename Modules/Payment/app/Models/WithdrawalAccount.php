<?php

namespace Modules\Payment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Payment\Database\Factories\WithdrawalAccountFactory;

class WithdrawalAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id' ,
        'recipient_code',
        'account_number',
        'account_name',
        'bank_code',
        'bank_name',
        'provider',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // protected static function newFactory(): WithdrawalAccountFactory
    // {
    //     // return WithdrawalAccountFactory::new();
    // }
}
