<?php

namespace Modules\Cooperative\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Cooperative\Database\Factories\CooperativeApiKeyFactory;

class CooperativeApiKey extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
     protected $fillable = [
        'cooperative_id',
        'name',
        'key_hash',
        'abilities',
        'last_used_at',
        'revoked_at',
    ];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function isActive(): bool
    {
        return is_null($this->revoked_at);
    }

    // protected static function newFactory(): CooperativeApiKeyFactory
    // {
    //     // return CooperativeApiKeyFactory::new();
    // }
}
