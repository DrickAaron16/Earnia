<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'results',
        'rng_seed',
        'rng_hash',
        'verification_payload',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'results' => 'array',
        'verification_payload' => 'array',
        'verified_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'results',
        'rng_seed',
        'rng_hash',
        'verification_payload',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'results' => 'array',
        'verification_payload' => 'array',
        'verified_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }
}

