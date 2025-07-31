<?php

namespace Database\Factories;

use App\Models\Clip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Clip>
 */
class ClipFactory extends Factory
{
    protected $model = Clip::class;

    public function definition(): array
    {
        return [
            'uuid'       => (string) Str::uuid(),
            'slug'       => $this->faker->slug,
            'url'        => $this->faker->url,
            'name_video' => $this->faker->words(3, true),
            'status'     => 'queued',          // або randomElement(...)
            'user_id'    => User::factory(),
            // решта шляхів можемо лишити null
            'video_path' => null,
            'wav_path'   => null,
            'vtt_path'   => null,
            'hard_path'  => null,
        ];
    }
}
