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
];
