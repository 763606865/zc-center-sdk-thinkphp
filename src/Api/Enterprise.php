<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP\Api;

use ZcCenter\ThinkPHP\Response;

/**
 * 企业与职工。
 *
 * @see docs/sapi/企业.md
 */
class Enterprise extends AbstractApi
{
    public const ROLE_ADMIN = 1;
    public const ROLE_MEMBER = 2;

    /**
     * 上报企业（按统一社会信用代码幂等）。
     *
     * POST /sapi/enterprise/report
     *
     * @param array<string, mixed> $payload
     */
    public function report(array $payload): Response
    {
        return $this->post('/sapi/enterprise/report', $payload);
    }

    /** 按 enterprise_uuid、credit_code 或 enterprise_code 获取详情。 */
    public function detail(array $payload): Response
    {
        return $this->post('/sapi/enterprise/detail', $payload);
    }

    /**
     * 加入职工。
     *
     * POST /sapi/enterprise/member/add
     *
     * @param array<string, mixed> $payload
     */
    public function addMember(array $payload): Response
    {
        return $this->post('/sapi/enterprise/member/add', $payload);
    }

    /**
     * 移除职工。
     *
     * POST /sapi/enterprise/member/remove
     *
     * @param array<string, mixed> $payload
     */
    public function removeMember(array $payload): Response
    {
        return $this->post('/sapi/enterprise/member/remove', $payload);
    }
}
