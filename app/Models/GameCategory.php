<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GameCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // Relation avec les jeux
    public function games()
    {
        return $this->hasMany(Game::class)->where('is_active', true)->orderBy('position', 'asc');
    }

    // Relation avec tous les jeux (y compris inactifs)
    public function allGames()
    {
        return $this->hasMany(Game::class)->orderBy('position', 'asc');
    }
}
