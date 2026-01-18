<?php

namespace Modules\Vault\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Modules\Vault\Enums\VaultStatusEnum;
// use Modules\Vault\Database\Factories\VaultFactory;

class Vault extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'description',
        'owner_id',
        'status',
        'interval',
        'total_amount',
        'no_of_save',
        'start_date',
        'maturity_date',
        'oweing',
        'last_save'
    ];

    protected $cast = [
        'no_of_save' => 'integer',
        'total_amount' => 'decimal:2',
        'status' => VaultStatusEnum::class
    ];

    public function owner()
    {
        return $this->belongsTo(User::class,'owner_id');
    }

    public function amount()
    {
        return 10;
    }
    // protected static function newFactory(): VaultFactory
    // {
    //     // return VaultFactory::new();
    // }
}
