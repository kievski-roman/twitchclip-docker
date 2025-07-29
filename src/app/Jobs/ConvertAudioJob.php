<?php
namespace App\Jobs;

use App\Enums\ClipStatus;
use App\Models\Clip;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConvertAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public Clip $clip) {}

    public function handle(): void
    {
        $relativeWav = "audio/{$this->clip->uuid}.wav";
        $absoluteWav = storage_path('app/public/' . $relativeWav);

        File::ensureDirectoryExists(dirname($absoluteWav));

        $videoPath = storage_path('app/public/' . $this->clip->video_path);

        $cmd = [
            'ffmpeg', '-y',
            '-i', $videoPath,
            '-ar', '16000',
            '-ac', '1',
            '-c:a', 'pcm_s16le',
            $absoluteWav,
        ];

        (new Process($cmd))->setTimeout(300)->mustRun();

        $this->clip->update([
            'wav_path' => $relativeWav,
            'status'   => ClipStatus::AUDIO_DONE,
        ]);

        TranscribeJob::dispatch($this->clip)->onQueue('transcribe');
    }
}
