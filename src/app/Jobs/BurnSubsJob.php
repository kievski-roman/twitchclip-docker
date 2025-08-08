<?php
namespace App\Jobs;

use App\Models\Clip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use App\Enums\ClipStatus;

class BurnSubsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Clip  $clip,
        public array $style = [],
        public string $ratio
    ) {}

    public function handle(): void
    {
        $basePath = storage_path('app/public');
        File::ensureDirectoryExists("$basePath/temp");
        File::ensureDirectoryExists("$basePath/hard");

        $vttRel = $this->clip->vtt_path;
        $uuid   = $this->clip->uuid;
        $assRel = "temp/{$uuid}.ass";
        $outRel = "hard/{$uuid}_hardsub.mp4";

        // 1) VTT → ASS
        (new Process(['ffmpeg','-y','-i',$vttRel,$assRel], $basePath))
            ->setTimeout(300)->mustRun();

        // 2) Инъекция стилей
        $assAbs  = "$basePath/$assRel";
        $content = File::get($assAbs);

        // primary colour
        $hex      = ltrim($this->style['color'] ?? '#FFFF00', '#');
        $rr       = substr($hex,0,2); $gg = substr($hex,2,2); $bb = substr($hex,4,2);
        $primary  = "&H00{$bb}{$gg}{$rr}";

        // outline colour
        $hexO     = ltrim($this->style['outline'] ?? '#000000', '#');
        $rrO      = substr($hexO,0,2); $ggO = substr($hexO,2,2); $bbO = substr($hexO,4,2);
        $outline  = "&H00{$bbO}{$ggO}{$rrO}";

        // secondary & back = transparent
        $secondary = "&H00000000";
        $backColor = "&H00000000";

        // bold/italic
        $fontStyle = $this->style['fontStyle'] ?? 'normal';
        $bold      = stripos($fontStyle,'bold')   !== false ? 1 : 0;
        $italic    = stripos($fontStyle,'italic') !== false ? 1 : 0;

        // fontSize
        $fontSize  = intval($this->style['fontSize'] ?? 24);

        // build Style:
        $newStyle = sprintf(
            "Style: Default,Arial,%d,%s,%s,%s,%s,%d,%d,0,0,100,100,0,0,1,1,0,2,10,10,10,1",
            $fontSize, $primary, $secondary, $outline, $backColor, $bold, $italic
        );

        $content = preg_replace('/^Style:.*$/m', $newStyle, $content);
        File::put($assAbs, $content);

        if ($this->ratio === '9:16') {
            $vf = sprintf(
                'ass=%s,scale=1080:-2,pad=1080:1920:(ow-iw)/2:(oh-ih)/2:black',
                $assRel
            );
        } else {
            $vf = "ass={$assRel}";
        }

        $burn = new Process([
            'ffmpeg','-y',
            '-i', $this->clip->video_path,
            '-vf', $vf,
            '-c:v','libx264',
            '-c:a','copy',
            $outRel
        ], $basePath);

        $burn->setTimeout(600)->mustRun();

        // 4) Обновляем модель
        $this->clip->update([
            'hard_path' => $outRel,
            'status'    => ClipStatus::HARD_DONE,
        ]);
    }
//    protected function logError($massege)
//    {
//        Log::error("[FFMPEG ERROR] Clip #{$this->clip->id} - {$massege}]");
//        $this->clip->update([
//            'status' => 'ERROR',
//            'last_error' => $massege,
//        ]);
//    }

}
