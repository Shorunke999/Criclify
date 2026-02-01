<?php

namespace Modules\Cooperative\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Cooperative\Enums\RepaymentFrequencyEnum;

// use Modules\Cooperative\Database\Factories\CoopRuleFactory;

class CoopRule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cooperative_id',
        'cooperative_minimum_amount',
        'loan_multiplier',
        'repayment_frequency',
        'max_active_loan',
    ];

    protected $casts = [
        'cooperative_minimum_amount' => 'decimal:2',
        'loan_multiplier' => 'decimal:2',
        'repayment_frequency' => RepaymentFrequencyEnum::class
    ];
    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }
    // protected static function newFactory(): CoopRuleFactory
    // {
    //     // return CoopRuleFactory::new();
    // }
}
