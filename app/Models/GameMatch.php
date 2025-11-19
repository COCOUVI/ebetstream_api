<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameMatch extends Model
{
    protected $fillable = [
        'game_id',
        'team1_name',
        'team2_name',
        'description',
        'match_date',
        'status',
        'result',
        'team1_odds',
        'draw_odds',
        'team2_odds',
        'is_active',
    ];

    protected $casts = [
        'match_date' => 'datetime',
        'is_active' => 'boolean',
        'team1_odds' => 'decimal:2',
        'draw_odds' => 'decimal:2',
        'team2_odds' => 'decimal:2',
    ];

    // Relation avec le jeu
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    // Relation avec les paris
    public function bets()
    {
        return $this->hasMany(Bet::class);
    }
}
