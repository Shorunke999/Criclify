<?php

namespace Modules\Waitlist\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Waitlist\Database\Factories\WaitlistEntryFactory;

// use Modules\Waitlist\Database\Factories\WaitlistEntryFactory;

class WaitlistEntry extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */

     protected $fillable = [
        'name',
        'email',
        'referral_code',
        'referral_count',
        'survey_data',
    ];

    protected $casts = [
        'survey_data' => 'array',
    ];
    protected static function newFactory(): WaitlistEntryFactory
    {
        return WaitlistEntryFactory::new();
    }
}
