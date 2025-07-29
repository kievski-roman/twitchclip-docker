<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WhisperService;

class WhisperProxyController extends Controller
{
    public function store(Request $request, WhisperService $whisper)
    {
        $request->validate([
            'audio' => 'required|file|mimetypes:audio/wav,audio/mpeg',
        ]);

        $path = $request->file('audio')->store('tmp');

        $absolute = storage_path('app/'.$path);
        $srt      = $whisper->transcribe($absolute);

        return response()->json(['srt' => $srt]);
    }
}

