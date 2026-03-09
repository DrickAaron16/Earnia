<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameSessionPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'user_id',
        'wallet_transaction_id',
        'bet_amount',
        'score',
        'status',
        'placement',
        'payout_amount',
        'is_winner',
        'joined_at',
    ];

    protected $casts = [
        'bet_amount' => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'is_winner' => 'boolean',
        'joined_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameSessionPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'user_id',
        'wallet_transaction_id',
        'bet_amount',
        'score',
        'status',
        'placement',
        'payout_amount',
        'is_winner',
        'joined_at',
    ];

    protected $casts = [
        'bet_amount' => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'is_winner' => 'boolean',
        'joined_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }
}

