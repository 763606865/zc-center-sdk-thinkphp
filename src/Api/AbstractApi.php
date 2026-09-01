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
        return $this->client->post($path, $this->compact($payload), $query);
    }

    protected function get(string $path, array $query = []): Response
    {
        return $this->client->get($path, $this->compact($query));
    }

    protected function request(
        string $method,
        string $path,
        array $payload = [],
        array $query = []
    ): Response {
        return $this->client->request($method, $path, $this->compact($payload), $this->compact($query));
    }

    /**
     * 去掉 null，避免把未使用的可选参数带进请求体。
     * 保留 false / 0 / [] / ''（空字符串由调用方自行决定是否传入）。
     *
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    protected function compact(array $params): array
    {
        $out = [];
        foreach ($params as $key => $value) {
            if ($value === null) {
                continue;
            }
            $out[$key] = $value;
        }

        return $out;
    }
}
