<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP;

use think\facade\Log;
use think\Service as ThinkService;

final class Service extends ThinkService
{
    public function register(): void
    {
        $defaults = require dirname(__DIR__) . '/config/zc_center.php';
        $configured = $this->app->config->get('zc_center', []);
        $config = array_replace($defaults, is_array($configured) ? $configured : []);

        if (!isset($config['logger']) && class_exists(Log::class)) {
            $config['logger'] = static function (string $message, array $context = []): void {
                Log::info($message, $context);
            };
        }

        $this->app->config->set($config, 'zc_center');

        $this->app->bind(Client::class, fn (): Client => new Client(
            $this->app->config->get('zc_center', [])
        ));
        $this->app->bind('zc_center', Client::class);
    }
}
