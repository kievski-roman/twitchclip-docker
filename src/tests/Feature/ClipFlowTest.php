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
    use RefreshDatabase;   // скидає базу після кожного тесту

    public function test_full_clip_flow(): void
    {
        Storage::fake('public');
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);


        $this->post('/clip/download', [
            'url' => 'https://www.twitch.tv/some/clip/slug',
        ])->assertRedirect();

        $clip = Clip::first();
        $this->assertEquals(ClipStatus::QUEUED, $clip->status);
        Queue::assertPushed(DownloadClipJob::class);

        /** Шаг 2: емуляція — кліп завантажено й SRT готовий */
        $clip->update(['status' => ClipStatus::READY]);

        $this->put("/clips/{$clip->id}/srt", [
            'srt' => "1\n00:00:00,000 --> 00:00:01,000\nHello",
        ])->assertNoContent();

        $clip->refresh();
        $this->assertEquals(ClipStatus::READY, $clip->status);

        /** Шаг 3: користувач натискає Generate */
        $this->post("/clips/{$clip->id}/hardsubs")->assertRedirect();
        Queue::assertPushed(BurnSubsJob::class);

        /** Шаг 4: емуляція ffmpeg */
        $path = 'hard/test.mp4';
        Storage::disk('public')->put($path, 'dummy-content');
        $clip->update(['status' => ClipStatus::HARD_DONE, 'hard_path' => $path]);

        /** Шаг 5: користувач завантажує готовий файл */
        $this->get("/clips/{$clip->id}/download")->assertOk();
    }
}
