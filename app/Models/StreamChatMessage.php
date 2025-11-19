<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamChatMessage extends Model
{
    protected $fillable = [
        'stream_id',
        'user_id',
        'message',
        'type',
        'is_moderator',
        'is_subscriber',
        'reply_to',
        'is_deleted',
    ];

    protected $casts = [
        'is_moderator' => 'boolean',
        'is_subscriber' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(StreamChatMessage::class, 'reply_to');
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }
}
