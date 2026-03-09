<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RngAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'seed',
        'hash',
        'algorithm',
        'transcript',
        'generated_at',
    ];

    protected $casts = [
        'transcript' => 'array',
        'generated_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }
}

