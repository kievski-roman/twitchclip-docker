<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TwitchApiService
{
    protected string $clientId = '';
    protected string $accessToken = '';

    public function __construct()
    {
        $this->clientId = config('services.twitch.client_id','');
        $this->accessToken = config('services.twitch.access_token','');
    }

    public function getUserIdByName(string $name): ?string
    {
        if ($this->accessToken === '') {
            return null;
        }

        $response = Http::withHeaders([
            'Client-ID' => $this->clientId,
            'Authorization' => 'Bearer ' . $this->accessToken,
        ])->get('https://api.twitch.tv/helix/users', [
            'login' => $name,
        ]);

        return $response->json('data.0.id') ?? null;
    }

    public function getClipsByUserId(string $broadcasterId, int $count = 5, ?string $after = null): array
    {
        if ($this->accessToken === '') {
            return [];
        }
        $params = [
                'broadcaster_id' => $broadcasterId,
                'first'          => $count,
            ] + ($after !== null ? ['after' => $after] : []);

        $response = Http::withHeaders([
            'Client-ID' => $this->clientId,
            'Authorization' => 'Bearer ' . $this->accessToken,
        ])->get('https://api.twitch.tv/helix/clips', $params);
        $cursor = $response->json( 'pagination.cursor' );
        $data = $response->json( 'data' );
        return [
            'data'   => $data,
            'cursor' => $cursor,
        ];
    }
}

