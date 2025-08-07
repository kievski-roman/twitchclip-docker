<?php

namespace Tests\Feature;

use App\Enums\ClipStatus;
use App\Jobs\BurnSubsJob;
use App\Jobs\DownloadClipJob;
use App\Models\Clip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClipFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_clip_flow_with_style_and_ratio(): void
    {
        // 1) Подменяем диск и очередь
        Storage::fake('public');
        Queue::fake();

        // 2) Логинимся
        $user = User::factory()->create();
        $this->actingAs($user);

        // 3) POST /clip/download — в очередь на DownloadClipJob
        $this->post(route('clip.download'), [
            'url' => 'https://www.twitch.tv/some/clip/slug',
        ])->assertRedirect();

        $clip = Clip::first();
        $this->assertEquals(ClipStatus::QUEUED, $clip->status);
        Queue::assertPushed(DownloadClipJob::class, fn($job) => $job->clip->is($clip));

        // 4) Эмулируем, что DownloadClipJob кончился и VTT готов
        $clip->update(['status' => ClipStatus::READY]);

        // Нужны и путь и файл, чтобы updateVtt() не ругался на NULL
        $vttRel = "vtt/{$clip->uuid}.vtt";
        Storage::disk('public')->put($vttRel, "WEBVTT\n\n");
        $clip->update(['vtt_path' => $vttRel]);

        // 5) PUT /clips/{clip}/vtt — сохраняем новый текст
        $this->putJson(route('clips.vtt', $clip), [
            'vtt' => "WEBVTT\n\n00:00:00.000 --> 00:00:01.000\nHello",
        ])->assertOk();

        $this->assertEquals(ClipStatus::READY, $clip->fresh()->status);

        // 6) PATCH /clips/{clip}/style — сохраняем JSON-стили
        $style = [
            'color'     => '#ff00ff',
            'fontSize'  => 28,
            'outline'   => '#000000',
            'fontStyle' => 'italic',
            'ratio'     => '9:16',
        ];
        $this->patchJson(route('clips.style', $clip), ['style' => $style])
            ->assertOk();

        $clip = $clip->fresh();
        $this->assertEquals(ClipStatus::READY, $clip->status);
        $this->assertEquals($style, $clip->vtt_style);

        // 7) POST /clips/{clip}/hardsubs — в очередь на BurnSubsJob с нужными параметрами
        $this->post(route('clips.hardsubs', $clip), ['style' => $style])
            ->assertRedirect();

        Queue::assertPushed(BurnSubsJob::class, function ($job) use ($clip, $style) {
            return $job->clip->is($clip)
                && $job->style === $style
                && $job->ratio === $style['ratio'];
        });

        $this->assertEquals(ClipStatus::HARD_PROCESSING, $clip->fresh()->status);

        // 8) Эмулируем, что джоб создал файл и обновил статус
        $hardPath = "hard/{$clip->uuid}_hardsub.mp4";
        Storage::disk('public')->put($hardPath, 'dummy-content');
        $clip->update([
            'status'    => ClipStatus::HARD_DONE,
            'hard_path' => $hardPath,
        ]);

        // 9) GET /clips/{clip}/download — качаем готовый MP4
        $this->get(route('clips.download', $clip))
            ->assertOk()
            // Laravel отдаёт без кавычек
            ->assertHeader(
                'content-disposition',
                'attachment; filename='.$clip->slug.'.mp4'
            );
    }
}
