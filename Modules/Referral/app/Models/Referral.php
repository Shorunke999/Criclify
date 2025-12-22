<?php

namespace Modules\Referral\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Referral\Database\Factories\ReferralFactory;

class Referral extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'referrer_id',
        'referred_id',
        'referral_type'
    ];


    // protected static function newFactory(): ReferralFactory
    // {
    //     // return ReferralFactory::new();
    // }
}
