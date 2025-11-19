<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    protected $fillable = [
        'creator_id',
        'name',
        'description',
        'status',
        'starts_at',
        'ends_at',
        'settings'
    ];

    protected $casts = [
        'settings' => 'json',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime'
    ];
}
