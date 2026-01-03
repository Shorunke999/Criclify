<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Casts\EncryptedString;
use Modules\Core\Database\Factories\UserMetaFactory;

// use Modules\Core\Database\Factories\UserMetaFactory;

class UserMeta extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'referral_code',
        'referral_count',

        'account_number',
        'alternate_account_number',
        'bvn',

        'data',

    ];

    protected $table = 'user_metas';

    protected $casts = [
        'data' => 'array',
        'account_number' => EncryptedString::class,
        'alternate_account_number' => EncryptedString::class,
        'bvn' => EncryptedString::class,
    ];

    protected $hidden = [
        'account_number',
        'alternate_account_number',
        'bvn',
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): UserMetaFactory
    {
        return UserMetaFactory::new();
    }
}
