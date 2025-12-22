<?php

namespace Modules\Core\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Core\Database\Factories\AuditTrailFactory;

class AuditTrail extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */

       protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'metadata',
        'ip_address',
        'user_agent',
        'version',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    // protected static function newFactory(): AuditTrailFactory
    // {
    //     // return AuditTrailFactory::new();
    // }
}
