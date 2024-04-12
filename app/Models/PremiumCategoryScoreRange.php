<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PremiumCategoryScoreRange extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'min',
        'max',
        'verdict'
    ];
}
