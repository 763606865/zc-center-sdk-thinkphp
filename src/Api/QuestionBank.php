<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP\Api;

use ZcCenter\ThinkPHP\Response;

/**
 * 题库 SAPI。
 *
 * @see docs/sapi/题库.md
 */
class QuestionBank extends AbstractApi
{
    /**
     * 列出当前应用可访问的已发布题库。
     *
     * POST /sapi/question-bank/list
     *
     * @param array{page?: int, page_size?: int, keyword?: string} $params
     */
    public function list(array $params = []): Response
    {
        return $this->post('/sapi/question-bank/list', $params);
    }

    /**
     * 按 UUID 获取题库详情（含已发布题目数 question_count）。
     *
     * POST /sapi/question-bank/detail
     */
    public function detail(string $uuid): Response
    {
        return $this->post('/sapi/question-bank/detail', [
            'uuid' => $uuid,
        ]);
    }
}
