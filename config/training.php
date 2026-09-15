<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Training video files
    |--------------------------------------------------------------------------
    |
    | Where the admin training videos, their caption files, thumbnails and
    | manifest.json live. Outside the web root on purpose: the files are only
    | ever sent through the logged-in admin route, never linked directly.
    |
    | The files are produced by tools/training-video (see
    | docs/training/ADMIN_VIDEO_GUIDE_STRUCTURE.md) and are not kept in git.
    |
    */

    'video_path' => env('TRAINING_VIDEO_PATH', storage_path('app/private/training-videos')),

    /*
    | A chapter counts as watched once this share of it has been played.
    */

    'complete_ratio' => 0.9,

];
