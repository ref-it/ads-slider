<?php

return [
    'color' => env('MIX_SLIDES_MAIN_COLOR', '#FF0000'),
    'font-color' => env('MIX_SLIDES_TEXT_COLOR', '#FFFFFF'),
    'background-color' => env('MIX_SLIDES_BACKGROUND_COLOR', '#000000'),
    'pic_basepath' => env('PIC_FOLDER', '/uploads/pics/'),
    'vid_basepath' => env('VID_FOLDER', '/uploads/videos/'),
    'menu_basepath' => env('MENU_FOLDER', '/uploads/menus/'),
    'report_issue_url' => env('REPORT_ISSUE_URL', 'https://github.com/bedo2991/ads-slider/issues/new'),
    'supported_locales' => ['en', 'de', 'it'],
];
