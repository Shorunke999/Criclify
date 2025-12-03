<?php

namespace App\Models;

use App\Enum\LogType;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = [
        'type',
        'message',
        'user_id'
    ];

    protected $casts = [
        'type' => LogType::class
    ];
}
