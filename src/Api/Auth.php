<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP\Api;

use ZcCenter\ThinkPHP\Response;

/**
 * 中台标准 SAPI 接口集合。
 *
 * 生态产品特有接口应放在自己的 AbstractApi 子类中，不要继续堆叠到底层 Client。
 */
class Auth extends AbstractApi
{
    public function issueTicket(string $uuid, string $targetAppCode): Response
    {
        return $this->post('/sapi/auth/ticket', [
            'uuid' => $uuid,
            'target_app_code' => $targetAppCode,
        ]);
    }

    public function exchangeTicket(string $ticket): Response
    {
        return $this->post('/sapi/auth/ticket/exchange', ['ticket' => $ticket]);
    }
}
