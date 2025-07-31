<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clips', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('slug', 255);
            $table->text('url');
            $table->string('name_video', 255);
            $table->string('hard_path')->nullable();
            $table->text('video_path')->nullable();
            $table->text('wav_path')->nullable();
            $table->text('vtt_path')->nullable();

            $table->string('status');               // queued / video_done / audio_done / ready / failed
            $table->foreignId('user_id')->nullable(); // додаси зв’язок, коли введеш users

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clips');
    }
};
