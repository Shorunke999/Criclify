<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Auth\Database\Factories\InviteCompliancesFactory;

class InviteCompliances extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
   
    protected $fillable = [
        'user_id',
        'creator_context',
        'organisation_context',
        'not_a_bank_acknowledged',
        'no_fund_safeguard_acknowledged',
        'fixed_payout_acknowledged',
        'agree_to_terms',
        'additional_context',
    ];

    protected $casts = [
        'creator_context' => 'array',
        'organisation_context' => 'array',
        'not_a_bank_acknowledged' => 'boolean',
        'no_fund_safeguard_acknowledged' => 'boolean',
        'fixed_payout_acknowledged' => 'boolean',
        'agree_to_terms' => 'boolean',
    ];

    /**
     * Optional link once user is created
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    // protected static function newFactory(): InviteCompliancesFactory
    // {
    //     // return InviteCompliancesFactory::new();
    // }
}
