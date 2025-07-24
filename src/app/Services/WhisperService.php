<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class WhisperService
{
    /**
     * Розпізнає мовлення й повертає шлях до .srt або null.
     */
    public function transcribe(string $wav, ?string $lang = null): ?string
    {
        // 1. Беремо шляхи з config/services.php
        $bin   = config('services.whisper.bin_path');    // exe / cli
        $model = config('services.whisper.model_path');  // *.bin
        $lang  = $lang ?: 'auto';

        // 2. Перевіряємо на місці чи ні
        if (!is_file($bin) || !is_file($model)) {
            logger()->error('Whisper paths invalid', compact('bin', 'model'));
            return null;
        }

        // 3. Куди складати субтитри
        $outDir = storage_path('app/subtitles');
        File::ensureDirectoryExists($outDir);

        $outBase = $outDir.'/'.pathinfo($wav, PATHINFO_FILENAME);

        // 4. Запускаємо whisper-cli
        $process = new Process([
            $bin,
            '-f', $wav,
            '-m', $model,
            '-l', $lang,
            '-osrt',          // генеруємо SRT
            '-of', $outBase,   // output directory
        ]);

        try {
            $process->setTimeout(300)->mustRun();
        } catch (ProcessFailedException $e) {
            logger()->error('whisper failed', [
                'stderr' => $e->getProcess()->getErrorOutput(),
            ]);
            return null;
        }

        // 5. Шлях до очікуваного файлу <basename>.srt
        $srt = $outBase.'.srt';
        // 6. Повертаємо шлях, якщо файл дійсно існує
        return is_file($srt) ? $srt : null;
    }
}

