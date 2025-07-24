<?php

namespace Tests\Unit;

use App\Models\Clip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ClipPolicyTest extends TestCase
{
    use RefreshDatabase;
    public function test_foreign_clip_is_forbidden(): void
    {
        [$alice, $bob] = User::factory()->count(2)->create();
        $clip = Clip::factory()->for($alice)->create();

        $this->assertFalse(
            Gate::forUser($bob)->allows('download', $clip)
        );
    }
}

