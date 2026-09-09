<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP\Api;

use ZcCenter\ThinkPHP\Response;

/** 招考公告 SAPI。 */
class ExamNotice extends AbstractApi
{
    /** @param array{last_uuid?:string, limit?:int} $params */
    public function list(array $params = []): Response
    {
        return $this->post('/sapi/exam-notice/list', $params);
    }

    /** @param array<string,mixed> $notice */
    public function report(array $notice): Response
    {
        return $this->post('/sapi/exam-notice/report', $notice);
    }

    /** @param list<array<string,mixed>> $items */
    public function reportBatch(array $items): Response
    {
        return $this->post('/sapi/exam-notice/report-batch', ['items' => $items]);
    }
}
