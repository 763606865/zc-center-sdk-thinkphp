<?php

declare(strict_types=1);

return [
    'base_url' => env('ZC_CENTER.BASE_URL', ''),
    'app_key' => env('ZC_CENTER.APP_KEY', ''),
    'app_secret' => env('ZC_CENTER.APP_SECRET', ''),
    'encryption' => (bool) env('ZC_CENTER.ENCRYPTION', true),
    'timeout' => (float) env('ZC_CENTER.TIMEOUT', 10),
    'connect_timeout' => (float) env('ZC_CENTER.CONNECT_TIMEOUT', 3),
    'verify_ssl' => (bool) env('ZC_CENTER.VERIFY_SSL', true),
    // 开启后记录请求头、请求参数、响应头、响应内容（含解密后业务体）
    'debug' => (bool) env('ZC_CENTER.DEBUG', true),
];
