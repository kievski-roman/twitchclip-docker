<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhisperService
{
    public function transcribe(string $absPath): string
    {
        return Http::attach(
            'audio',
            fopen($absPath, 'r'),
            basename($absPath)
        )
            ->post('http://whisper:9000/transcribe')
            ->json('vtt', '');
    }
}



