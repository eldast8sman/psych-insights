<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_package_id',
        'payment_plan_id',
        'amount_paid',
        'start_date',
        'end_date',
        'grace_end',
        'auto_renew',
        'status'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
