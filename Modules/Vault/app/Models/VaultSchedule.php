<?php

namespace Modules\Vault\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Payment\Models\Transaction;
use Modules\Vault\Enums\VaultScheduleStatusEnum;
use Modules\Vault\Database\Factories\VaultScheduleFactory;

class VaultSchedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'vault_id',
        'due_date',
        'status',
        'paid_at'
    ];

    protected $cast = [
        'status' => VaultScheduleStatusEnum::class
    ];
    public function vault()
    {
        return $this->belongsTo(Vault::class);
    }
    public function transactions()
    {
        return $this->morphToMany(Transaction::class, 'transactable')
            ->withPivot('amount')
            ->withTimestamps();
    }
    protected static function newFactory(): VaultScheduleFactory
    {
         return VaultScheduleFactory::new();
    }
}
