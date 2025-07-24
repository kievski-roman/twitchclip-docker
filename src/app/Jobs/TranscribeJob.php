<?php

namespace App\Jobs;

use App\Enums\ClipStatus;
use App\Models\Clip;
use App\Services\WhisperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class TranscribeJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Clip $clip) {}

    /**
     * Execute the job.
     */

    public function handle(WhisperService $whisper): void
    {
        $wav = storage_path('app/public/' . $this->clip->wav_path);

        $srt = $whisper->transcribe($wav, $this->clip->lang);

        if (!$srt || !is_file($srt)) {
            $this->clip->update(['status' => ClipStatus::FAILED]);
            return;
        }

        $relativeSrt = "str/{$this->clip->uuid}.srt";

        // Записуємо результат whisper через Storage
        Storage::disk('public')->put($relativeSrt, file_get_contents($srt));

        $this->clip->update([
            'srt_path'         => $relativeSrt,
            'transcript_plain' => strip_tags(file_get_contents($srt)),
            'status'           => ClipStatus::READY,
        ]);

        // Видаляємо тимчасові файли
        @unlink($wav);
    //    @unlink($srt);
    }

}
