<?php

namespace App\Http\Controllers;

use App\Enums\ClipStatus;
use App\Models\Clip;
use App\Services\TwitchApiService;
use Illuminate\Http\Request;

class ClipApiController extends Controller
{

    protected TwitchApiService $twitch;



    public function __construct(TwitchApiService $twitch)
    {
        $this->twitch = $twitch;
    }
    //
    public function status(Clip $clip)
    {
        return [
            'status' => $clip->status,

            'url'    => $clip->status === ClipStatus::HARD_DONE
                ? route('clips.download', $clip)
                : null,
        ];
    }
    public function getClipsJson(Request $request, string $username )
    {
        $userId = $this->twitch->getUserIdByName($username);
        if (! $userId) {
            return back()->withErrors(['username' => 'Користувача не знайдено']);
        }
        $after = $request->input('after');
        $count = $request->input('count', '5');

        $raw = $this->twitch->getClipsByUserId($userId, (int)$count, $after);
        $data = $raw['data'];

        $clips = collect($data)->map(function ($c) {
            return [
                'url'      => $c['url'],
                'title'    => $c['title'],              // ← тут та самая строка
                'filename' => basename($c['url']) . '.mp4',
                'thumbnail_url' => $c['thumbnail_url'], // если нужно
            ];
        });

        return response()->json([
            'data'   => $clips->values(),
            'cursor' => $raw['cursor'] ?? null,
        ]);

    }
}
