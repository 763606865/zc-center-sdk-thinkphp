<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP\Api;

use ZcCenter\ThinkPHP\Response;

class User extends AbstractApi
{
    public function register(
        string $mobile,
        string $countryCode = '+86',
        ?string $nickname = null,
        ?string $avatar = null
    ): Response {
        return $this->post('/sapi/user/register', array_filter([
            'mobile' => $mobile,
            'country_code' => $countryCode,
            'nickname' => $nickname,
            'avatar' => $avatar,
        ], static fn (mixed $value): bool => $value !== null));
    }
}
