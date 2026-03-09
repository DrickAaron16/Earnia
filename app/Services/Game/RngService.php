<?php

namespace App\Services\Game;

use App\Models\GameSession;
use App\Models\RngAuditLog;

class RngService
{
    public function issueSeed(GameSession $session, array $context = []): array
    {
        $seed = bin2hex(random_bytes(32));
        $hash = hash('sha256', $seed);
        $signature = hash_hmac('sha256', $hash.(string) $session->id, config('app.key'));

        $session->forceFill([
            'rng_seed' => $seed,
            'rng_signature' => $signature,
        ])->save();

        RngAuditLog::create([
            'game_session_id' => $session->id,
            'seed' => $seed,
            'hash' => $hash,
            'algorithm' => 'sha256',
            'transcript' => $context,
            'generated_at' => now(),
        ]);

        return [
            'seed' => $seed,
            'hash' => $hash,
            'signature' => $signature,
        ];
    }

    public function verify(string $seed, string $hash): bool
    {
        return hash_equals(hash('sha256', $seed), $hash);
    }
}

