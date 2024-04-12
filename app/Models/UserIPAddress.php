<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserIPAddress extends Model
{
    use HasFactory;

    protected $table = "user_ip_addresses";

    protected $fillable = [
        'user_id',
        'ip_address',
        'country',
        'location_details',
        'frequency'
    ];
}
