<?php

namespace Modules\Circle\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Circle\Database\Factories\CircleContributionFactory;
use Modules\Circle\Enums\StatusEnum;

// use Modules\Circle\Database\Factories\CircleContributionFactory;

class CircleContribution extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
      protected $fillable = [
        'circle_id',
        'circle_member_id',
        'cycle_index',
        'amount',
        'paid_amount',
        'status',
        'due_date',
        'paid_at',
        'transaction_id',
    ];

    protected $casts = [
        'status' => StatusEnum::class,
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];


    protected static function newFactory(): CircleContributionFactory
    {
        return CircleContributionFactory::new();
    }

     /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function circle(): BelongsTo
    {
        return $this->belongsTo(Circle::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CircleMember::class, 'circle_member_id');
    }
}
