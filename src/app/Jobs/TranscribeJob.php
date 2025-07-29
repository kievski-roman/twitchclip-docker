<?php

namespace App\Jobs;

use App\Enums\ClipStatus;
use App\Models\Clip;
use App\Services\WhisperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class TranscribeJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Clip $clip) {}

    public function handle(WhisperService $whisper): void
    {
        $wav = storage_path('app/public/' . $this->clip->wav_path);

        if (! is_file($wav)) {
            $this->clip->update(['status' => ClipStatus::FAILED]);
            return;
        }

        // 1️отримуємо SRT‑ТЕКСТ
        $srtText = $whisper->transcribe($wav);

        if ($srtText === '') {
            $this->clip->update(['status' => ClipStatus::FAILED]);
            return;
        }

        $relative = "str/{$this->clip->uuid}.srt";

        // 2️зберігаємо
        Storage::disk('public')->put($relative, $srtText);

        // 3️оновлюємо модель
        $this->clip->update([
            'srt_path'         => $relative,
            'transcript_plain' => strip_tags($srtText),
            'status'           => ClipStatus::READY,
        ]);

        // 4️почистили tmp‑wav
        @unlink($wav);
    }
}

