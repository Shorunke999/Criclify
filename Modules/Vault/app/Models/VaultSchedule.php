<?php

namespace Modules\Vault\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    protected static function newFactory(): VaultScheduleFactory
    {
         return VaultScheduleFactory::new();
    }
}
