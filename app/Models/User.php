<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\AccountStatus;
use App\Enums\KycStatus;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Models\UserMeta;
use Modules\Referral\Models\Referral;
use Spatie\Permission\Traits\HasRoles;
use Modules\Core\Models\Wallet;
use Modules\Core\Traits\HasWallet;
use Modules\Payment\Models\ProviderAccount;
use Modules\Payment\Models\WithdrawalAccount;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasRoles,HasApiTokens,HasWallet;

    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'account_status',
        'kyc_status',
        'kyc_verified_at',
        'ndpr_consent',
        'reviewed_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'kyc_status' => KycStatus::class,
            'kyc_verified_at' => 'datetime',
            'ndpr_consent' => 'boolean',
            'account_status' =>AccountStatus::class

        ];
    }

    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function meta()
    {
        return $this->hasOne(UserMeta::class,'user_id');
    }
    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'walletable');
    }

    public function depositAccounts()
    {
        return $this->hasMany(ProviderAccount::class,'user_id');
    }
    public function withdrawalAccounts()
    {
        return $this->hasMany(WithdrawalAccount::class,'user_id');
    }

    public function cooperative()
    {
        return $this->hasOne(\Modules\Cooperative\Models\Cooperative::class,'owner_id');
    }
    public function inviteCompliance()
    {
        return $this->hasOne(\Modules\Auth\Models\InviteCompliances::class,'user_id');
    }
}
