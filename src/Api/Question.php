<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP\Api;

use ZcCenter\ThinkPHP\Response;

/**
 * 题目 SAPI。
 *
 * @see docs/sapi/题库.md
 */
class Question extends AbstractApi
{
    public const TYPE_SINGLE = 1;
    public const TYPE_MULTI = 2;
    public const TYPE_JUDGE = 3;
    public const TYPE_BLANK = 4;
    public const TYPE_ESSAY = 5;

    public const DIFFICULTY_EASY = 1;
    public const DIFFICULTY_MEDIUM = 2;
    public const DIFFICULTY_HARD = 3;

    /**
     * 分页获取可访问的已发布题目。
     *
     * POST /sapi/question/list
     *
     * @param array{
     *   page?: int,
     *   page_size?: int,
     *   bank_uuid?: string,
     *   keyword?: string,
     *   type?: int,
     *   difficulty?: int,
     *   tag_uuids?: list<string>,
     *   updated_since?: int,
     *   include_answer?: bool
     * } $params
     */
    public function list(array $params = []): Response
    {
        return $this->post('/sapi/question/list', $params);
    }

    /**
     * 搜索已发布题目（有关键词时优先 ES，可回退 MySQL）。
     *
     * POST /sapi/question/search
     *
     * 参数与 {@see list()} 相同。
     *
     * @param array{
     *   page?: int,
     *   page_size?: int,
     *   bank_uuid?: string,
     *   keyword?: string,
     *   type?: int,
     *   difficulty?: int,
     *   tag_uuids?: list<string>,
     *   updated_since?: int,
     *   include_answer?: bool
     * } $params
     */
    public function search(array $params = []): Response
    {
        return $this->post('/sapi/question/search', $params);
    }

    /**
     * 按 UUID 获取题目详情。
     *
     * POST /sapi/question/detail
     */
    public function detail(string $uuid, bool $includeAnswer = false): Response
    {
        return $this->post('/sapi/question/detail', [
            'uuid' => $uuid,
            'include_answer' => $includeAnswer,
        ]);
    }

    /**
     * 按 UUID 批量拉取题目（最多 100）。
     *
     * POST /sapi/question/batch
     *
     * @param list<string> $uuids
     */
    public function batch(array $uuids, bool $includeAnswer = false): Response
    {
        return $this->post('/sapi/question/batch', [
            'uuids' => array_values($uuids),
            'include_answer' => $includeAnswer,
        ]);
    }

    /**
     * 向当前应用归属的题库上报题目（按规范化题干幂等去重）。
     *
     * 依赖配置 `report_enabled`；目标题库默认取 `report_bank_uuid` / `report_bank_code`，
     * 也可在 payload 中显式传入 `bank_uuid` / `bank_code` 覆盖。
     *
     * POST /sapi/question/report
     *
     * @param array{
     *   bank_uuid?: string,
     *   bank_code?: string,
     *   type: int,
     *   stem: string,
     *   difficulty?: int,
     *   options?: mixed,
     *   answer: mixed,
     *   analysis?: string|null,
     *   score?: float|int,
     *   category_uuid?: string,
     *   tag_uuids?: list<string>,
     *   knowledge_uuids?: list<string>,
     *   media?: mixed
     * } $payload
     */
    public function report(array $payload): Response
    {
        $bank = $this->client->resolveReportBank([
            'bank_uuid' => $payload['bank_uuid'] ?? null,
            'bank_code' => $payload['bank_code'] ?? null,
        ]);
        unset($payload['bank_uuid'], $payload['bank_code']);

        return $this->post('/sapi/question/report', array_merge($bank, $payload));
    }

    /**
     * 批量上报题目（单次最多 100 条，按题干去重）。
     *
     * 目标题库默认使用配置；`$bank` 非空时覆盖配置。
     *
     * POST /sapi/question/report-batch
     *
     * @param list<array<string,mixed>> $items
     * @param array{bank_uuid?: string, bank_code?: string} $bank
     */
    public function reportBatch(array $items, array $bank = []): Response
    {
        return $this->post('/sapi/question/report-batch', array_merge(
            $this->client->resolveReportBank($bank),
            ['items' => array_values($items)]
        ));
    }
}
