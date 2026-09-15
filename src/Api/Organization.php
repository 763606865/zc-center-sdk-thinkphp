<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP\Api;

use ZcCenter\ThinkPHP\Response;

/** 组织管理。参见 docs/sapi/组织.md。 */
class Organization extends AbstractApi
{
    public const TYPE_ENTERPRISE = 'enterprise';
    public const TYPE_SCHOOL = 'school';
    public const TYPE_GOVERNMENT = 'government';
    public const TYPE_PUBLIC_INSTITUTION = 'public_institution';
    public const TYPE_ASSOCIATION = 'association';
    public const TYPE_LAW_FIRM = 'law_firm';
    public const TYPE_HR_AGENCY = 'hr_agency';
    public const TYPE_MEDICAL = 'medical';
    public const TYPE_FOUNDATION = 'foundation';
    public const TYPE_COMMUNITY = 'community';
    public const TYPE_OTHER = 'other';

    /** 按 external_id 幂等上报，或按 organization_uuid 更新。 */
    public function report(array $payload): Response
    {
        return $this->post('/sapi/organization/report', $payload);
    }

    /** 按 organization_uuid 或 external_id 获取详情。 */
    public function detail(array $payload): Response
    {
        return $this->post('/sapi/organization/detail', $payload);
    }
}
