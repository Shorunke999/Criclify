<?php

namespace Modules\Circle\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Circle\Database\Factories\CircleMemberFactory;
use Modules\Circle\Enums\StatusEnum;

// use Modules\Circle\Database\Factories\CircleMemberFactory;

class CircleMember extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
      protected $fillable = [
        'circle_id',
        'user_id',
        'position',
        'no_of_times',
        'paid_status',
        'paid_at',
        'amount_paid',
        'next_payment_due'
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'paid_at' => 'datetime',
        'next_payment_due' => 'datetime',
        'paid_status' => StatusEnum::class
    ];

    public function circle(): BelongsTo
    {
        return $this->belongsTo(Circle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contributions():HasMany
    {
        return $this->hasMany(CircleContribution::class);
    }

    protected static function newFactory(): CircleMemberFactory
    {
         return CircleMemberFactory::new();
    }
}
