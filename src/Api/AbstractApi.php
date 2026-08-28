<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP\Api;

use ZcCenter\ThinkPHP\Client;
use ZcCenter\ThinkPHP\Response;

/**
 * 生态产品自定义接口集合的基类。
 *
 * 子类只描述接口路径及业务参数；签名、加密、验签和异常处理统一交给 Client。
 */
abstract class AbstractApi
{
    public function __construct(protected readonly Client $client)
    {
    }

    protected function post(string $path, array $payload = [], array $query = []): Response
    {
        return $this->client->post($path, $payload, $query);
    }

    protected function get(string $path, array $query = []): Response
    {
        return $this->client->get($path, $query);
    }

    protected function request(
        string $method,
        string $path,
        array $payload = [],
        array $query = []
    ): Response {
        return $this->client->request($method, $path, $payload, $query);
    }
}
