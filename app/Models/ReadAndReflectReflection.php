<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadAndReflectReflection extends Model
{
    use HasFactory;

    protected $fillable = [
        'read_and_reflect_id',
        'reflection'
    ];
}
