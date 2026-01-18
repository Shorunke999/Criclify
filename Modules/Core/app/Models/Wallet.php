<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Enums\WalletTypeEnum;
use Modules\Circle\Models\Circle;
use App\Models\User;
// use Modules\Core\Database\Factories\WalletFactory;

class Wallet extends Model
{
    use HasFactory;

     protected $fillable = [
        'circle_id',
        'user_id',
        'balance',
        'currency',
        'type'
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'type' => WalletTypeEnum::class
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function circle()
    {
        return $this->belongsTo(Circle::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
