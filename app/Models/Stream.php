<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Stream extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'stream_key',
        'rtmp_url',
        'hls_url',
        'thumbnail',
        'category',
        'game',
        'viewer_count',
        'follower_count',
        'is_live',
        'started_at',
        'ended_at',
        'settings',
    ];

    protected $casts = [
        'is_live' => 'boolean',
        'viewer_count' => 'integer',
        'follower_count' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'settings' => 'json',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($stream) {
            if (!$stream->stream_key) {
                $stream->stream_key = Str::random(32);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(StreamSession::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(StreamChatMessage::class);
    }

    public function followers(): HasMany
    {
        return $this->hasMany(StreamFollower::class);
    }

    public function scopeLive($query)
    {
        return $query->where('is_live', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByGame($query, $game)
    {
        return $query->where('game', $game);
    }
}
