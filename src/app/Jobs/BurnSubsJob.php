<?php
namespace App\Jobs;

use App\Models\Clip;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use App\Enums\ClipStatus;

class BurnSubsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Clip $clip) {}

    public function handle(): void
    {
        // Робоча директорія: storage/app/public
        $basePath = storage_path('app/public');

        // Відносні шляхи з БД
        $videoPath = $this->clip->video_path;  // videos/xxx.mp4
        $subsPath  = $this->clip->vtt_path;    // str/xxx.vtt
        $outputRel = 'hard/' . $this->clip->uuid . '_hardsub.mp4';

        // Повний шлях для результату
        $outputAbs = $basePath . '/' . $outputRel;

        File::ensureDirectoryExists(dirname($outputAbs));

        // Команда ffmpeg із робочою директорією storage/app/public
        $process = new Process([
            'ffmpeg', '-y',
            '-i', $videoPath,
            '-vf', "subtitles=$subsPath",
            '-c:v', 'libx264',
            $outputAbs,
        ], $basePath);  // ← тут вказано базову папку!


        $process->setTimeout(600)->mustRun();

        // оновлюємо Clip після створення хард-сабів
        $this->clip->update([
            'hard_path' => $outputRel,
            'status'    => ClipStatus::HARD_DONE,
        ]);
    }
}
