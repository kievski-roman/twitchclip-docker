<?php

namespace App\Enums;

enum ClipStatus: string
{
    case QUEUED          = 'queued';
    case READY           = 'ready';
    case HARD_PROCESSING = 'hard_processing';
    case HARD_DONE       = 'hard_done';
    case AUDIO_DONE      = 'audio_done';
    case VIDEO_DONE      = 'video_done';
    case FAILED          = 'failed';      // опційно
}
