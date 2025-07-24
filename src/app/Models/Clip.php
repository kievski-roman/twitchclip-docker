<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ClipStatus;
use Illuminate\Notifications\Notifiable;

class Clip extends Model
{
    use HasFactory, Notifiable;
    //
    protected $fillable = [
        'uuid', 'slug', 'url', 'name_video',
        'video_path', 'wav_path', 'srt_path', 'hard_path',
        'status', 'user_id',
    ];
    protected $casts = [
        'status' => ClipStatus::class,
    ];
    public function user(){
        return $this->belongsTo(User::class);
    }
}
