<?php

namespace Modules\Payment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Circle\Models\Circle;
use Modules\Circle\Models\CircleContribution;
use Modules\Core\Models\Wallet;
use Modules\Payment\Database\Factories\TransactionFactory;
use Modules\Payment\Enums\TransactionStatusEnum;
use Modules\Payment\Enums\TransactionTypeEnum;
use Modules\Vault\Models\Vault;
use Modules\Vault\Models\VaultSchedule;

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
        'vault_id',
        'wallet_id',
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
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function circle()
    {
        return $this->belongsTo(Circle::class);
    }
    public function vault()
    {
        return $this->belongsTo(Vault::class);
    }
    public function transactables()
    {
        return $this->hasMany(Transactable::class);
    }
    public function contributions()
    {
        return $this->morphedByMany(CircleContribution::class, 'transactable')
            ->withPivot('amount')
            ->withTimestamps();
    }
    public function vaultSchedules()
    {
        return $this->morphedByMany(VaultSchedule::class, 'transactable')
            ->withPivot('amount')
            ->withTimestamps();
    }
    protected static function newFactory(): TransactionFactory
    {
         return TransactionFactory::new();
    }
}
