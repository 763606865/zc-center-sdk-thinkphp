<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP;

use think\Service as ThinkService;

final class Service extends ThinkService
{
    public function register(): void
    {
        $defaults = require dirname(__DIR__) . '/config/zc_center.php';
        $configured = $this->app->config->get('zc_center', []);
        $config = array_replace($defaults, is_array($configured) ? $configured : []);
        $this->app->config->set($config, 'zc_center');

        $this->app->bind(Client::class, fn (): Client => new Client(
            $this->app->config->get('zc_center', [])
        ));
        $this->app->bind('zc_center', Client::class);
    }
}
