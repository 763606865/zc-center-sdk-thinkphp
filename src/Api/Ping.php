<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP\Api;

use ZcCenter\ThinkPHP\Response;

class Ping extends AbstractApi
{
    public function __invoke(string $echo = 'hello'): Response
    {
        return $this->send($echo);
    }

    public function send(string $echo = 'hello'): Response
    {
        return $this->post('/sapi/ping', ['echo' => $echo]);
    }
}
