<?php

namespace Modules\Cooperative\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Cooperative\Enums\CoopMemberStatuEnum;

// use Modules\Cooperative\Database\Factories\CoopMemberFactory;

class CoopMember extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
     protected $fillable = [
        'cooperative_id',
        'user_id',
        'full_name',
        'phone_number',
        'email',
        'status',
        'joined_at',
    ];

    protected $casts = [
        'status' => CoopMemberStatuEnum::class,
        'joined_at' => 'datetime',
    ];
    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // protected static function newFactory(): CoopMemberFactory
    // {
    //     // return CoopMemberFactory::new();
    // }
}
