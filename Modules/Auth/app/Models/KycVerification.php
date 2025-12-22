<?php

namespace Modules\Auth\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\KycStatus;

// use Modules\Auth\Database\Factories\KycVerificationFactory;

class KycVerification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'job_id',
        'smile_job_id',
        'country',
        'id_type',
        'status',
        'result_code',
        'result_text',
        'actions',
        'personal_info',
        'document_image',
        'error_message',
    ];

     protected $casts = [
        'actions' => 'array',
        'personal_info' => 'array',
        'status'=> KycStatus::class
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    // protected static function newFactory(): KycVerificationFactory
    // {
    //     // return KycVerificationFactory::new();
    // }
}
