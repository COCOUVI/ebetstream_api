<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'opponent_id',
        'game',
        'bet_amount',
        'status',
        'creator_score',
        'opponent_score',
        'expires_at',
    ];

    protected $casts = [
        'bet_amount' => 'decimal:2',
        'creator_score' => 'integer',
        'opponent_score' => 'integer',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the creator of the challenge.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the opponent of the challenge.
     */
    public function opponent()
    {
        return $this->belongsTo(User::class, 'opponent_id');
    }

    /**
     * Scope to get only open challenges.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open')
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to get challenges for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('creator_id', $userId)
              ->orWhere('opponent_id', $userId);
        });
    }
}
