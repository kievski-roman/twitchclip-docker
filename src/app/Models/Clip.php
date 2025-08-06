<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ClipStatus;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Clip extends Model
{
    use HasFactory, Notifiable;
    //
    protected $fillable = [
        'uuid', 'slug', 'url', 'name_video',
        'video_path', 'wav_path', 'vtt_path', 'hard_path',
        'status', 'user_id','vtt_style',
    ];
    protected $casts = [
        'status' => ClipStatus::class,
        'vtt_style' => 'array',
    ];
    public function user(){
        return $this->belongsTo(User::class);
    }

}
