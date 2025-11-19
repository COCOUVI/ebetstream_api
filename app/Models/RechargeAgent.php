<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RechargeAgent extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'status',
        'description',
    ];

    /**
     * Scope pour récupérer uniquement les agents actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
