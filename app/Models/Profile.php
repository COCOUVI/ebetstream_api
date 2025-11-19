<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'full_name',
        'in_game_pseudo',
        'status',
        'avatar',
        'profile_photo',
        'country',
        'bio',
        'wins',
        'losses',
        'ratio',
        'qr_code',
        'profile_url',
        'tournaments_won',
        'tournaments_list',
        'ranking',
        'division',
        'global_score',
        'current_season',
        'badges',
        'certifications',
    ];

    protected $casts = [
        'wins' => 'integer',
        'losses' => 'integer',
        'ratio' => 'float',
        'tournaments_won' => 'integer',
        'global_score' => 'integer',
        'tournaments_list' => 'array',
        'badges' => 'array',
        'certifications' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
