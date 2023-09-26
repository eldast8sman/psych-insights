<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripeCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
        'customer_data'
    ];
}
