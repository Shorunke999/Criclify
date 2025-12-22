<?php

namespace Modules\Waitlist\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Waitlist\Database\Factories\WaitlistQuestionFactory;

// use Modules\Waitlist\Database\Factories\WaitlistQuestionFactory;

class WaitlistQuestion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
     protected $fillable = [
        'key',
        'label',
        'type',
        'options',
        'required',
        'active',
    ];

    protected $cast = [
        'options' => 'array'
    ];
    protected static function newFactory(): WaitlistQuestionFactory
    {
        return WaitlistQuestionFactory::new();
    }
}
