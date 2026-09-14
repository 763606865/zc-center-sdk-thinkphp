<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP\Api;

use ZcCenter\ThinkPHP\Response;

/** 招考岗位 SAPI。 */
class ExamPosition extends AbstractApi
{
    /** @param array{notice_uuid:string, updated_after?:int, last_id?:int, limit?:int} $params */
    public function list(array $params): Response
    {
        return $this->post('/sapi/exam-position/list', $params);
    }

    /**
     * @param string $noticeUuid
     * @param list<array<string,mixed>> $items
     */
    public function reportBatch(string $noticeUuid, array $items, bool $syncIndex = false): Response
    {
        return $this->post('/sapi/exam-position/report-batch', [
            'notice_uuid' => $noticeUuid,
            'items' => $items,
            'sync_index' => $syncIndex,
        ]);
    }
}
