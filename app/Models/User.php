<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="username", type="string", example="johndoe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+237123456789"),
 *     @OA\Property(property="pseudo", type="string", nullable=true, example="JohnGamer"),
 *     @OA\Property(property="avatar", type="string", nullable=true, example="https://example.com/avatar.jpg"),
 *     @OA\Property(property="level", type="integer", example=5),
 *     @OA\Property(property="total_wins", type="integer", example=10),
 *     @OA\Property(property="total_losses", type="integer", example=3),
 *     @OA\Property(property="total_earnings", type="number", format="float", example=150.50),
 *     @OA\Property(property="is_verified", type="boolean", example=true),
 *     @OA\Property(property="kyc_verified", type="boolean", example=false),
 *     @OA\Property(property="status", type="string", example="active"),
 *     @OA\Property(property="wallet", ref="#/components/schemas/Wallet"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'phone',
        'phone_number',
        'country_code',
        'date_of_birth',
        'birth_date',
        'is_verified',
        'kyc_verified',
        'is_admin',
        'avatar',
        'pseudo',
        'level',
        'total_wins',
        'total_losses',
        'total_earnings',
        'status',
        'last_login_at',
        'firebase_uid',
        'provider',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'birth_date' => 'date',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'kyc_verified' => 'boolean',
            'is_admin' => 'boolean',
            'total_earnings' => 'decimal:2',
        ];
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }

    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    public function eventParticipants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isAdult(): bool
    {
        if (!$this->date_of_birth) {
            return false;
        }

        return $this->date_of_birth->age >= 18;
    }

}
