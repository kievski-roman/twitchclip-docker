<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class VideoController extends Controller
{
    //
    public function showClips($filename){
        if(!Storage::disk('public')->exists('videos/' . $filename)){
            return back()->with('no clip');
        }
        return view('clips-result',[
            'filename' => $filename
        ]);
    }
}
