<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Game extends Model
{
    protected $fillable = [
        'game_category_id',
        'name',
        'slug',
        'description',
        'icon',
        'image',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
        'game_category_id' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($game) {
            if (empty($game->slug)) {
                $game->slug = Str::slug($game->name);
            }
        });
    }

    // Relation avec la catégorie
    public function category()
    {
        return $this->belongsTo(GameCategory::class, 'game_category_id');
    }
}
