<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bet extends Model
{
    protected $fillable = [
        'user_id',
        'game_match_id',
        'bet_type',
        'amount',
        'potential_win',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'potential_win' => 'decimal:2',
    ];

    // Relation avec l'utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation avec le match
    public function gameMatch()
    {
        return $this->belongsTo(GameMatch::class);
    }
}
