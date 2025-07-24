<?php

namespace App\Http\Controllers;

use App\Enums\ClipStatus;
use App\Models\Clip;
use Illuminate\Http\Request;

class ClipApiController extends Controller
{
    //
    public function status(Clip $clip)
    {
        return [
            'status' => $clip->status,

            'url'    => $clip->status === ClipStatus::HARD_DONE
                ? route('clips.download', $clip)
                : null,
        ];
    }
}
