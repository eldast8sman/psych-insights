<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDeactivation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reason',
        'remarks'
    ];

    const UPDATED_AT = NULL;
}
