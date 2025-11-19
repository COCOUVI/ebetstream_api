<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'status',
        'issued_by',
        'meta'
    ];

    protected $casts = [
        'meta' => 'json'
    ];
}
