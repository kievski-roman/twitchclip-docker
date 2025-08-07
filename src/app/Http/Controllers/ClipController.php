<?php

namespace App\Http\Controllers;

use App\Enums\ClipStatus;
use App\Http\Requests\DownloadClipRequest;
use App\Http\Requests\UpdateStyleRequest;
use App\Http\Requests\UpdateVttRequest;
use App\Jobs\BurnSubsJob;
use App\Jobs\DownloadClipJob;
use App\Models\Clip;
use App\Services\TwitchApiService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
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

        if (! $userId) {
            return back()->withErrors(['username' => 'Користувача не знайдено']);
        }

        $raw = $this->twitch->getClipsByUserId($userId);

        $clips = collect($raw)->map(function ($c) {
            return [
                'url'      => $c['url'],
                'title'    => $c['title'],              // ← тут та самая строка
                'filename' => basename($c['url']) . '.mp4',
                'thumbnail_url' => $c['thumbnail_url'], // если нужно
            ];
        });

        return view('clip-result', compact('clips', 'username'));
    }
    public function searchUserAndRedirect(Request $request)
    {
        $request->validate(['username' => 'required|string']);

        return redirect()->route('clip.result', ['username' => $request->input('username')]);
    }

    // Метод POST /clip/download
    public function download(DownloadClipRequest $request)
    {
        Log::info('❗️ DownloadClipRequest отримано, payload:', $request->all());

        $data = $request->validated();
        /** @var Clip $clip */
        $clip = Clip::firstOrCreate(
            ['slug' => basename($data['url'])],
            [
                'uuid'   => (string) Str::uuid(),
                'url'    => $data['url'],
                'name_video' => $data['title'],
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

    public function updateVtt(UpdateVttRequest $request, Clip $clip)
    {
        $relative = "vtt/{$clip->uuid}.vtt";


        Log::debug('WRITE_TO', ['rel' => $relative, 'db' => $clip->vtt_path]);
        Log::debug('EXIST?', [
            'rel_exists' => Storage::disk('public')->exists($relative),
            'db_exists'  => Storage::disk('public')->exists($clip->vtt_path),
        ]);


        $result = Storage::disk('public')->put($relative, $request->vtt);
        if (! $result) {
            Log::error("Не вдалося записати Vtt: $relative");
        }
        if($request->has('style')){
            $clip->update(['vtt_style' => $request->input('style')]);
        }

        // змінюємо тільки updated_at, бо vtt_path і status не змінюються
        $clip->touch();

        return response($request->vtt, 200)
            ->header('Content-Type', 'text/plain')
            ->header('Cache-Control', 'no-store');
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

        if (! Str::startsWith($clip->vtt_path, ['/', 'C:', 'D:'])) {
            $subs = Storage::disk('public')->exists($clip->vtt_path)
                ? Storage::disk('public')->get($clip->vtt_path)
                : '';
        } else {
            $subs = is_file($clip->vtt_path)
                ? file_get_contents($clip->vtt_path)
                : '';
        }

        return view('clip-show', [
            'clip'        => $clip,
            'videoUrl'    => $videoUrl,
            'subs'        => $subs,
            'styleUrl'    => route('clips.style',   $clip),
            'generateUrl' => route('clips.hardsubs',$clip),
            'downloadUrl' => route('clips.download', $clip),
            'statusUrl'   => route('api.clips.status',$clip),
        ]);
    }
    public function generateHardSubs(Request $request,Clip $clip)
    {
        $style = $request->input('style', [
            'color'    => '#ffff00',
            'fontSize' => 24,
            'outline' => '#000000',
            'fontStyle' => 'normal',
            'ratio'     => '16:9',
        ]);
        $clip->update([
            'status'    => ClipStatus::HARD_PROCESSING,
            'vtt_style' => $style,
        ]);

        BurnSubsJob::dispatch(
            $clip,
            $style,
            $style['ratio'] ?? '16:9'
        )->onQueue('hardsubs');

        return back()->with('flash', 'Почали генерацію відео з hard-сабами!');
    }
    public function downloadHardSub(Clip $clip)
    {
        $this->authorize('download', $clip);
        abort_unless($clip->status === ClipStatus::HARD_DONE, 404);

        return Storage::disk('public')
            ->download($clip->hard_path, $clip->slug . '.mp4');
    }
    public function updateStyle(UpdateStyleRequest $request, Clip $clip)
    {
        $clip->update([
            'vtt_style' => $request->input('style'),
            'status'    => ClipStatus::READY,
        ]);
        return response()->json(['ok'=>true]);
    }
}
