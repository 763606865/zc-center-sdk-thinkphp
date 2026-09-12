<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP\Api;

use ZcCenter\ThinkPHP\Response;

/**
 * 职位库 SAPI。
 *
 * @see docs/sapi/职位.md
 */
class JobBank extends AbstractApi
{
    /**
     * 列出当前应用可访问的已发布职位库。
     *
     * POST /sapi/job-bank/list
     *
     * @param array{page?: int, page_size?: int, keyword?: string, last_uuid?: string} $params
     */
    public function list(array $params = []): Response
    {
        return $this->post('/sapi/job-bank/list', $params);
    }

    /**
     * 按 UUID 获取职位库详情（含已发布职位数 job_count）。
     *
     * POST /sapi/job-bank/detail
     */
    public function detail(string $uuid): Response
    {
        return $this->post('/sapi/job-bank/detail', [
            'uuid' => $uuid,
        ]);
    }
}
