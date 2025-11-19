<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ambassador extends Model
{
    protected $fillable = [
        'name',
        'username',
        'avatar',
        'score',
        'country',
        'bio',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'score' => 'integer',
        'position' => 'integer',
    ];
}
