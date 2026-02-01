<?php

namespace Modules\Cooperative\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Cooperative\Enums\CooperativeStatusEnum;
use Modules\Core\Models\Wallet;

// use Modules\Cooperative\Database\Factories\CooperativeFactory;

class Cooperative extends Model
{
    protected $fillable = [
        'name',
        'owner_id',
        'country_id',
        'organisation_name',
        'organisation_type',
        'organisation_reg_number',
        'organisation_established_year',
        'approx_member_number',
        'has_existing_scheme',
        'current_contribution_management',
        'governance_structure',
        'intended_api_usage',
        'organisation_handles_payments',
        'has_internal_default_rules',
        'status',
    ];
    protected $casts = [
        'intended_api_usage' => 'array',
        'status' => CooperativeStatusEnum::class,
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->hasMany(CoopMember::class);
    }

    public function rules()
    {
        return $this->hasOne(CoopRule::class);
    }

    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'walletable');
    }
}

