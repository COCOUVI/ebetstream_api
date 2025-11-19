<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StreamSession extends Model
{
    protected $fillable = [
        'stream_id',
        'session_id',
        'status',
        'peak_viewers',
        'total_viewers',
        'started_at',
        'ended_at',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'json',
        'peak_viewers' => 'integer',
        'total_viewers' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($session) {
            if (!$session->session_id) {
                $session->session_id = Str::uuid()->toString();
            }
        });
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }
}
