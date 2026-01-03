<?php

namespace Modules\Circle\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Circle\Database\Factories\CircleInviteFactory;
use Modules\Circle\Enums\InviteStatusEnum;
// use Modules\Circle\Database\Factories\CircleInviteFactory;

class CircleInvite extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
     protected $fillable = [
        'circle_id',
        'inviter_id',
        'invitee_id',
        'contact',
        'status',
        'token',
        'notified_at',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'notified_at' => 'datetime',
        'status' => InviteStatusEnum::class,
    ];

    public function circle()
    {
        return $this->belongsTo(Circle::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function invitee()
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }

    protected static function newFactory(): CircleInviteFactory
    {
        return CircleInviteFactory::new();
    }
}
