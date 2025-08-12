<?php

namespace App\Jobs;

use App\Enums\ClipStatus;
use App\Models\Clip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BurnSubsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Clip   $clip,
        public array  $style = [],
        public string $ratio = '16:9',
        public float  $start = 0.0,
        public ?float $end   = null,
    ) {}

    public function handle(): void
    {
        $disk = Storage::disk('public');

        // 1) Проверяем исходники: это КЛЮЧИ на диске, без "public/"
        $videoKey = ltrim((string) $this->clip->video_path, '/'); // e.g. "videos/xxx.mp4"
        $vttKey   = ltrim((string) $this->clip->vtt_path,   '/'); // e.g. "vtt/xxx.vtt"

        if (!$disk->exists($videoKey) || !$disk->exists($vttKey)) {
            Log::error('BurnSubsJob: source not found', [
                'clip'        => $this->clip->id,
                'video_key'   => $videoKey,
                'vtt_key'     => $vttKey,
                'video_abs'   => $disk->path($videoKey),
                'vtt_abs'     => $disk->path($vttKey),
            ]);
            throw new \RuntimeException('Source files not found');
        }

        $videoAbs = $disk->path($videoKey);
        $vttAbs   = $disk->path($vttKey);

        // 2) Готовим каталоги (ключи без "public/")
        $disk->makeDirectory('temp');
        $disk->makeDirectory('hard');

        $uuid       = $this->clip->uuid;
        $segVttKey  = "temp/{$uuid}.vtt";            // КЛЮЧИ
        $assKey     = "temp/{$uuid}.ass";
        $outKey     = "hard/{$uuid}_hardsub.mp4";

        $segVttAbs  = $disk->path($segVttKey);       // АБСОЛЮТНЫЕ пути
        $assAbs     = $disk->path($assKey);
        $outAbs     = $disk->path($outKey);

        // (опционально) ключ прогресса
        $progressKey = "clip:{$this->clip->id}:progress";

        try {
            // 3) Режем VTT на [start, end] и сдвигаем тайминги к нулю
            $this->sliceAndShiftVtt($vttAbs, $segVttAbs, $this->start, $this->end);

            // 4) VTT -> ASS
            (new Process(['ffmpeg', '-y', '-i', $segVttAbs, $assAbs]))
                ->setTimeout(300)
                ->mustRun();

            // 5) Внедряем стиль в ASS
            $this->injectAssStyle($assAbs, $this->style);

            // 6) Фильтр по соотношению
            $vf = ($this->ratio === '9:16')
                ? "ass={$assAbs},scale=1080:-2,pad=1080:1920:(ow-iw)/2:(oh-ih)/2:black"
                : "ass={$assAbs}";

            // 7) ffmpeg с точным тримом и (опционально) прогрессом
            $dur   = isset($this->end) ? max(0, $this->end - $this->start) : null;
            $durMs = $dur ? (int) round($dur * 1000) : null;

            $cmd = ['ffmpeg', '-y'];
            if ($this->start > 0) { $cmd[] = '-ss'; $cmd[] = (string) $this->start; }
            $cmd[] = '-i'; $cmd[] = $videoAbs;
            if ($dur !== null)    { $cmd[] = '-t';  $cmd[] = (string) $dur; }
            // прогресс в stderr
            array_push($cmd,
                '-vf', $vf,
                '-c:v', 'libx264', '-c:a', 'copy',
                '-progress', 'pipe:2', '-nostats',
                $outAbs
            );

            // стартуем прогресс с 0
            if (class_exists(\Illuminate\Support\Facades\Cache::class)) {
                \Illuminate\Support\Facades\Cache::put($progressKey, 0, now()->addHour());
            }

            (new Process($cmd))
                ->setTimeout(1800)
                ->mustRun(function ($type, $buffer) use ($durMs, $progressKey) {
                    // парсим out_time_ms=...
                    if (!class_exists(\Illuminate\Support\Facades\Cache::class) || !$durMs) return;

                    foreach (preg_split("/\r\n|\n|\r/", $buffer) as $line) {
                        if (str_starts_with($line, 'out_time_ms=')) {
                            $outMs = (int) substr($line, 12);
                            $pct = max(0, min(100, (int) round($outMs / $durMs * 100)));
                            \Illuminate\Support\Facades\Cache::put($progressKey, $pct, now()->addHour());
                        }
                        if ($line === 'progress=end') {
                            \Illuminate\Support\Facades\Cache::put($progressKey, 100, now()->addMinutes(5));
                        }
                    }
                });

            // 8) Обновляем модель (ХРАНИМ КЛЮЧ без "public/")
            $this->clip->update([
                'hard_path' => $outKey,
                'status'    => ClipStatus::HARD_DONE,
            ]);
        } catch (\Throwable $e) {
            // если хочешь — верни статус обратно в READY
            $this->clip->update(['status' => ClipStatus::READY]);
            throw $e;
        } finally {
            // 9) Чистим темп: удаляем по КЛЮЧАМ без всяких str_replace
            $disk->delete([$segVttKey, $assKey]);

            // (опционально) финал прогресса
            if (class_exists(\Illuminate\Support\Facades\Cache::class)) {
                \Illuminate\Support\Facades\Cache::put($progressKey, 100, now()->addMinutes(5));
            }
        }
    }


    public function failed(\Throwable $e): void
    {
        Log::error('BurnSubsJob failed', [
            'clip' => $this->clip?->id,
            'err'  => $e->getMessage(),
        ]);

        // Если у тебя нет ClipStatus::ERROR — откатываем на READY, чтобы можно было перезапустить
        $this->clip->update([
            'status' => ClipStatus::FAILED,
            'last_error' => $e->getMessage(),
        ]);
    }

    private function sliceAndShiftVtt(string $srcVttAbs, string $dstVttAbs, float $start, ?float $end): void
    {
        $text  = File::get($srcVttAbs);
        $lines = preg_split("/\r\n|\n|\r/", $text);
        $out   = ['WEBVTT', ''];

        $toSec = function (string $ts): float {
            [$hms, $ms] = array_pad(explode('.', str_replace(',', '.', $ts)), 2, '0');
            [$h, $m, $s] = array_map('intval', explode(':', $hms));
            return $h * 3600 + $m * 60 + $s + (int)$ms / 1000;
        };
        $fmt = function (float $n): string {
            if ($n < 0) $n = 0;
            $ms = (int)round(($n - floor($n)) * 1000);
            $t  = (int)floor($n);
            $s  = $t % 60; $t = intdiv($t - $s, 60);
            $m  = $t % 60; $h = intdiv($t - $m, 60);
            return sprintf('%02d:%02d:%02d.%03d', $h, $m, $s, $ms);
        };

        $segStart = $start;
        $segEnd   = $end ?? INF;

        for ($i = 0; $i < count($lines); $i++) {
            if (!preg_match('/^(\d{2}:\d{2}:\d{2}[.,]\d{3})\s-->\s(\d{2}:\d{2}:\d{2}[.,]\d{3})/', $lines[$i], $m)) {
                continue;
            }
            $from = $toSec($m[1]); $to = $toSec($m[2]);

            if ($to <= $segStart || $from >= $segEnd) {
                while ($i < count($lines) && trim($lines[$i]) !== '') $i++;
                continue;
            }

            $nf = max($from, $segStart) - $segStart;
            $nt = min($to,   $segEnd)   - $segStart;

            $out[] = $fmt($nf) . ' --> ' . $fmt($nt);

            $j = $i + 1;
            while ($j < count($lines) && trim($lines[$j]) !== '') { $out[] = $lines[$j]; $j++; }
            $out[] = '';
            $i = $j;
        }

        File::put($dstVttAbs, implode("\n", $out));
    }

    private function injectAssStyle(string $assAbs, array $style): void
    {
        $content = File::get($assAbs);

        $hex   = ltrim($style['color']   ?? '#FFFF00', '#');
        $hexO  = ltrim($style['outline'] ?? '#000000', '#');

        // BGR для ASS (&HAABBGGRR)
        $primary = sprintf('&H00%02s%02s%02s', substr($hex, 4, 2), substr($hex, 2, 2), substr($hex, 0, 2));
        $outline = sprintf('&H00%02s%02s%02s', substr($hexO,4, 2), substr($hexO,2, 2), substr($hexO,0, 2));

        $secondary = '&H00000000';
        $backColor = '&H00000000';

        $fontStyle = strtolower($style['fontStyle'] ?? 'normal');
        $bold      = str_contains($fontStyle, 'bold')   ? 1 : 0;
        $italic    = str_contains($fontStyle, 'italic') ? 1 : 0;
        $fontSize  = (int)($style['fontSize'] ?? 24);

        $newStyle = sprintf(
            'Style: Default,Arial,%d,%s,%s,%s,%s,%d,%d,0,0,100,100,0,0,1,1,0,2,10,10,10,1',
            $fontSize, $primary, $secondary, $outline, $backColor, $bold, $italic
        );

        if (preg_match('/^Style:.*$/m', $content)) {
            $content = preg_replace('/^Style:.*$/m', $newStyle, $content, 1);
        } else {
            $content = preg_replace('/^\[V4\+ Styles]\R/m', "[V4+ Styles]\n{$newStyle}\n", $content, 1);
        }

        File::put($assAbs, $content);
    }
}
