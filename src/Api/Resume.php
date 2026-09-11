<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP\Api;

use ZcCenter\ThinkPHP\Response;

/** 简历同步 SAPI。 */
class Resume extends AbstractApi
{
    /** @param array{updated_after?:int,last_id?:int,limit?:int} $params */
    public function list(array $params = []): Response { return $this->post('/sapi/resume/list', $params); }
    public function detail(string $uuid): Response { return $this->post('/sapi/resume/detail', ['uuid'=>$uuid]); }
    /** 新建或按 UUID 幂等更新。 */
    public function report(array $resume): Response { return $this->post('/sapi/resume/report', $resume); }
    /** 修改已存在简历，必须包含 uuid。 */
    public function update(array $resume): Response { return $this->post('/sapi/resume/update', $resume); }
}
