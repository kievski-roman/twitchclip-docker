<?php

namespace App\Http\Controllers;

use App\Enums\ClipStatus;
use App\Jobs\BurnSubsJob;
use App\Jobs\DownloadClipJob;
use App\Models\Clip;
use App\Services\TwitchApiService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class ClipController extends Controller
{
    use AuthorizesRequests;

    protected TwitchApiService $twitch;




    public function __construct(TwitchApiService $twitch)
    {
        $this->twitch = $twitch;
    }
    public function showForm()
{
    return view('clip-form');
}

    public function getClips(string $username)
    {
        $userId = $this->twitch->getUserIdByName($username);

        if (!$userId) {
            return back()->withErrors(['username' => 'Користувача не знайдено']);
        }

        $clips = $this->twitch->getClipsByUserId($userId);

        $clips = collect($clips)->map(function ($clip) {
            $slug = basename($clip['url']);
            $clip['filename'] = $slug . '.mp4';
            return $clip;
        });

        return view('clip-result', compact('clips', 'username'));
    }
    public function searchUserAndRedirect(Request $request)
    {
        $request->validate(['username' => 'required|string']);

        return redirect()->route('clip.result', ['username' => $request->input('username')]);
    }

    // Метод POST /clip/download
    public function download(Request $request)
    {
        $clip = Clip::firstOrCreate(
            ['slug' => basename($request->url)],
            [
                'uuid'   => (string) Str::uuid(),
                'url'    => $request->url,
                'name_video' => basename($request->url),
                'status' => ClipStatus::QUEUED,
                'user_id' => auth()->id(),
            ]
        );

        DownloadClipJob::dispatch($clip)->onQueue('video');

        return back()->with('flash', 'Кліп додано у чергу! Перевірте через хвилину.');
    }
    public function index(){
        $clips = Clip::whereIn('status', [
            ClipStatus::READY,
            ClipStatus::HARD_DONE
        ])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();
        return view('clip-index', compact('clips'));
    }

    public function updateSrt(Request $request, Clip $clip)
    {
        $data = $request->validate([
            'srt' => 'required|string',
        ]);

        // 1. Тільки ВІДНОСНИЙ шлях усередині диска 'public'
        $relative = "str/{$clip->uuid}.srt";

        // 2. Пишемо файл у storage/app/public/str/...
        Storage::disk('public')->put($relative, $data['srt']);

        // 3. Запам’ятовуємо без "D:\..." – лиш «public/...»
        $clip->update([
            'srt_path' => $relative,
            'status' => ClipStatus::READY,
        ]);

        return response()->noContent();      // 204
    }
    public function show(Clip $clip)
    {
        // ✅ Ось це правильна версія, яка підтримує і абсолютні, і відносні шляхи
        if (! Str::startsWith($clip->video_path, ['/', 'C:', 'D:'])) {
            $videoUrl = Storage::disk('public')->exists($clip->video_path)
                ? Storage::disk('public')->url($clip->video_path)
                : '';
        } else {
            // якщо шлях абсолютний
            $videoUrl = is_file($clip->video_path)
                ? asset(Str::after($clip->video_path, public_path('\\')))
                : '';
        }

        // ✅ так само роби для сабів (ти вже зробив):
        if (! Str::startsWith($clip->srt_path, ['/', 'C:', 'D:'])) {
            $subs = Storage::disk('public')->exists($clip->srt_path)
                ? Storage::disk('public')->get($clip->srt_path)
                : '';
        } else {
            $subs = is_file($clip->srt_path)
                ? file_get_contents($clip->srt_path)
                : '';
        }

        return view('clip-show', compact('clip', 'videoUrl', 'subs'));
    }
    public function generateHardSubs(Clip $clip)
    {
        $clip->update(['status' => ClipStatus::HARD_PROCESSING]);

        BurnSubsJob::dispatch($clip)
            ->onQueue('hardsubs');

        return back()->with('flash', 'Почали генерацію відео з hard-сабами!');
    }

    public function downloadHardSub(Clip $clip)
    {
        $this->authorize('download', $clip);
        abort_unless($clip->status === ClipStatus::HARD_DONE, 404);

        return Storage::disk('public')
            ->download($clip->hard_path, $clip->slug . '.mp4');
    }



}

