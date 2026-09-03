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
    // 题目上报：关闭时调用 report / reportBatch 会抛错；开启时须配置目标题库（uuid / code 二选一）
    'report_enabled' => (bool) env('ZC_CENTER.REPORT_ENABLED', false),
    'report_bank_uuid' => (string) env('ZC_CENTER.REPORT_BANK_UUID', ''),
    'report_bank_code' => (string) env('ZC_CENTER.REPORT_BANK_CODE', ''),
];
