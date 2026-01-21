<?php

namespace Modules\Circle\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Circle\Database\Factories\CircleFactory;
use Modules\Circle\Enums\{
    CircleStatusEnum,
    CircleIntervalEnum,
    PositionSelectionMethodEnum
};
use Modules\Core\Models\Wallet;
use Modules\Core\Traits\HasWallet;
use Modules\Payment\Models\Transaction;

class Circle extends Model
{
    use HasFactory,HasWallet;

    /**
     * The attributes that are mass assignable.
     */

     protected $fillable = [
        'name',
        'description',
        'code',
        'creator_id',
        'amount',
        'interval',
        'select_position_method',
        'status',
        'limit',
        'current_cycle',
        'start_date',
        'end_date',
        'multiple_position',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'limit' => 'integer',
        'status' => CircleStatusEnum::class,
        'interval' => CircleIntervalEnum::class,
        'select_position_method' => PositionSelectionMethodEnum::class,

    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CircleMember::class);
    }

    public function wallet()
    {
        return $this->morphOne(Wallet::class,'walletable');
    }

    public function invites()
    {
        return $this->hasMany(CircleInvite::class);
    }
    public function activeMembers(): HasMany
    {
        return $this->members()->where('paid_status', '!=', 'cancelled');
    }

    public function getNextPosition(): int
    {
        return $this->members()->max('position') + 1;
    }

    public function isFull(): bool
    {
        return $this->members()->count() >= $this->limit;
    }

    public function hasUnverifiedMembers(): bool
    {
        return $this->members()
             ->whereHas('user', fn ($q) => $q->whereNull('kyc_verified_at'))
            ->exists();
    }
    public function getRotationDays(): int
    {
        return match($this->interval) {
            'daily' => 1,
            'weekly' => 7,
            'biweekly' => 14,
            'monthly' => 30,
            default => 30,
        };
    }

    /**
     * Get cycle start date for Nth cycle from start
     */
    public function cycleDateByIndex(int $index): Carbon
    {
        return $this->start_date
            ->copy()
            ->addDays($index * $this->getRotationDays())
            ->startOfDay();
    }
    public function endDate(): Carbon
    {
        return $this->end_date ?? now()
            ->copy()
            ->addDays($this->limit * $this->getRotationDays())
            ->startOfDay();
    }
    protected static function newFactory(): CircleFactory
    {
        return CircleFactory::new();
    }
}
