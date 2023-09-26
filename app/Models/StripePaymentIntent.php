<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripePaymentIntent extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_ref',
        'user_id',
        'client_secret',
        'amount',
        'intent_id',
        'intent_data',
        'purpose',
        'purpose_id',
        'auto_renew',
        'value_given'
    ];
}
