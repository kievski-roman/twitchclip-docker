<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TwitchApiService
{
    protected string $clientId;
    protected string $accessToken;

    public function __construct()
    {
        $this->clientId = config('services.twitch.client_id');
        $this->accessToken = config('services.twitch.access_token');
    }

    public function getUserIdByName(string $name): ?string
    {
        $response = Http::withHeaders([
            'Client-ID' => $this->clientId,
            'Authorization' => 'Bearer ' . $this->accessToken,
        ])->get('https://api.twitch.tv/helix/users', [
            'login' => $name,
        ]);

        return $response->json('data.0.id') ?? null;
    }

    public function getClipsByUserId(string $broadcasterId, int $count = 5): array
    {
        $response = Http::withHeaders([
            'Client-ID' => $this->clientId,
            'Authorization' => 'Bearer ' . $this->accessToken,
        ])->get('https://api.twitch.tv/helix/clips', [
            'broadcaster_id' => $broadcasterId,
            'first' => $count,
        ]);

        return $response->json('data') ?? [];
    }
}
