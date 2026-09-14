<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP\Api;

use ZcCenter\ThinkPHP\Response;

/** 招考公告 SAPI。 */
class ExamNotice extends AbstractApi
{
    private const POSITION_BATCH = 100;

    /** @param array{last_uuid?:string, limit?:int, exam_year?:int, recruit_count?:int, recruit_count_min?:int, recruit_count_max?:int} $params */
    public function list(array $params = []): Response
    {
        return $this->post('/sapi/exam-notice/list', $params);
    }

    /** @param array<string,mixed> $notice */
    public function report(array $notice): Response
    {
        $positions = $this->extractPositions($notice);
        $response = $this->post('/sapi/exam-notice/report', $notice);
        $uuid = $this->noticeUuidFromData($response->data());
        if ($uuid === '') {
            $uuid = (string) ($notice['uuid'] ?? '');
        }
        $this->pushPositions($uuid, $positions);

        return $response;
    }

    /** @param list<array<string,mixed>> $items */
    public function reportBatch(array $items): Response
    {
        $stripped = [];
        $pending = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                $stripped[] = $item;
                $pending[] = [];
                continue;
            }
            $positions = $this->extractPositions($item);
            $stripped[] = $item;
            $pending[] = $positions;
        }

        $response = $this->post('/sapi/exam-notice/report-batch', ['items' => $stripped]);
        $data = $response->data();
        $results = is_array($data) ? ($data['results'] ?? []) : [];
        if (!is_array($results)) {
            return $response;
        }

        foreach ($results as $index => $row) {
            if (!is_array($row) || ($row['action'] ?? '') === 'failed') {
                continue;
            }
            $uuid = (string) (($row['notice']['uuid'] ?? '') ?: '');
            if ($uuid === '' && isset($stripped[$index]) && is_array($stripped[$index])) {
                $uuid = (string) ($stripped[$index]['uuid'] ?? '');
            }
            $this->pushPositions($uuid, $pending[$index] ?? []);
        }

        return $response;
    }

    /**
     * @param array<string,mixed> $notice
     * @return list<array<string,mixed>>
     */
    private function extractPositions(array &$notice): array
    {
        $positions = $notice['positions'] ?? [];
        unset($notice['positions']);

        return is_array($positions) ? array_values($positions) : [];
    }

    private function noticeUuidFromData(mixed $data): string
    {
        if (!is_array($data)) {
            return '';
        }

        return (string) (($data['notice']['uuid'] ?? '') ?: '');
    }

    /**
     * @param list<array<string,mixed>> $positions
     */
    private function pushPositions(string $noticeUuid, array $positions): void
    {
        if ($noticeUuid === '' || $positions === []) {
            return;
        }

        $chunks = array_chunk($positions, self::POSITION_BATCH);
        $last = count($chunks) - 1;
        foreach ($chunks as $i => $chunk) {
            $this->client->examPosition()->reportBatch($noticeUuid, $chunk, $i === $last);
        }
    }
}
