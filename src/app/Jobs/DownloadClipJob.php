<?php

namespace App\Jobs;

use App\Enums\ClipStatus;
use App\Models\Clip;
use App\Services\VideoDownloaderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;


class DownloadClipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Clip $clip) {}

    public function handle(VideoDownloaderService $downloader): void
    {
        // куди зберігаємо
        $relativeMp4 = "videos/{$this->clip->uuid}.mp4";
        $absoluteMp4 = storage_path('app/public/' . $relativeMp4);

        if (!Storage::disk('public')->exists($relativeMp4)) {
            $downloader->download($this->clip->url, $absoluteMp4);
        }

        $this->clip->update([
            'video_path' => $relativeMp4,
            'status'     => ClipStatus::VIDEO_DONE,
        ]);

        ConvertAudioJob::dispatch($this->clip)->onQueue('audio');
    }
}

