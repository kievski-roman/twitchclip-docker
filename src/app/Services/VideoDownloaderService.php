<?php


namespace App\Services;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class VideoDownloaderService
{
    /**
     * Завантажує кліп і повертає фактичний шлях до MP4.
     *
     * @throws \RuntimeException коли yt-dlp падає
     */
    public function download(string $url, string $outputPath): string
    {
        $process = new Process([
            'yt-dlp',
            '--no-part',
            '-o', $outputPath,
            $url,
        ]);

        $process->setTimeout(180);          // 3 хв
        try {
            $process->mustRun();
            return $outputPath;
        } catch (ProcessFailedException $e) {
            logger()->error('yt-dlp failed', ['stderr' => $e->getProcess()->getErrorOutput()]);
            throw new \RuntimeException('Download failed: '.$e->getMessage(), 0, $e);
        }
    }
}
