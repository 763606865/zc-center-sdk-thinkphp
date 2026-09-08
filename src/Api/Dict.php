<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP\Api;

use ZcCenter\ThinkPHP\Response;

/**
 * 数据字典增量拉取。
 *
 * @see docs/sapi/字典.md
 */
class Dict extends AbstractApi
{
    /**
     * 通用字典类型列表。
     *
     * POST /sapi/dict/types
     *
     * @param array<string, mixed> $payload
     */
    public function types(array $payload = []): Response
    {
        return $this->post('/sapi/dict/types', $payload);
    }

    /**
     * 通用字典项增量拉取。
     *
     * POST /sapi/dict/items
     *
     * @param array<string, mixed> $payload type_code, latest_code?, limit?
     */
    public function items(array $payload): Response
    {
        return $this->post('/sapi/dict/items', $payload);
    }

    /**
     * 主数据增量拉取（area/industry/occupation/major）。
     *
     * POST /sapi/dict/master
     *
     * @param array<string, mixed> $payload kind, latest_code?, limit?, level?, version?, education_level?
     */
    public function master(array $payload): Response
    {
        return $this->post('/sapi/dict/master', $payload);
    }
}
