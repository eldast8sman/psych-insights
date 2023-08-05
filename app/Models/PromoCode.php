<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'promo_code',
        'scope',
        'percentage_off',
        'usage_limit',
        'status'
    ];
}
