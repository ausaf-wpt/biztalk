<?php

return [
    'ffmpeg' => [
        'binaries' => env('FFMPEG_BINARIES', 'C:/ffmpeg/bin/ffmpeg.exe'),

        'threads' => 12,   // set to false to disable the default 'threads' filter
    ],

    'ffprobe' => [
        'binaries' => env('FFPROBE_BINARIES', 'C:/ffmpeg/bin/ffprobe.exe'),
    ],

    'timeout' => 3600,

    'log_channel' => env('LOG_CHANNEL', 'stack'),   // set to false to completely disable logging

    'temporary_files_root' => env('FFMPEG_TEMPORARY_FILES_ROOT', sys_get_temp_dir()),
];
