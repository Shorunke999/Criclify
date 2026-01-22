<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Core\Database\Factories\CountryFactory;

class Country extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'iso_code', 'currency_id', 'is_active', 'platform_fee_percentage',
        'circle_creation_fee_percentage',
        'is_active'];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    // protected static function newFactory(): CountryFactory
    // {
    //     // return CountryFactory::new();
    // }
}
