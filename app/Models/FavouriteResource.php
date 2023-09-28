<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavouriteResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'resource_id'
    ];
}
