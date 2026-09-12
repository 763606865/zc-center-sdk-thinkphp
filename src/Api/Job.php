<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP\Api;

use ZcCenter\ThinkPHP\Response;

/**
 * 职位同步 SAPI。
 *
 * @see docs/sapi/职位.md
 */
class Job extends AbstractApi
{
    /**
     * 增量拉取可访问职位（含删除 tombstone）。
     *
     * POST /sapi/job/list
     *
     * @param array{updated_after?: int, last_id?: int, limit?: int, bank_uuid?: string, bank_code?: string} $params
     */
    public function list(array $params = []): Response
    {
        return $this->post('/sapi/job/list', $params);
    }

    /** POST /sapi/job/detail */
    public function detail(string $uuid): Response
    {
        return $this->post('/sapi/job/detail', ['uuid' => $uuid]);
    }

    /**
     * 归属应用向自有职位库上报职位。
     *
     * POST /sapi/job/report
     *
     * @param array<string, mixed> $job
     */
    public function report(array $job): Response
    {
        return $this->post('/sapi/job/report', $job);
    }

    /**
     * 更新职位（归属可改内容+状态；订阅仅 status）。
     *
     * POST /sapi/job/update
     *
     * @param array<string, mixed> $job 必须含 uuid
     */
    public function update(array $job): Response
    {
        return $this->post('/sapi/job/update', $job);
    }
}
