<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP;

use think\Facade as ThinkFacade;
use ZcCenter\ThinkPHP\Api\AbstractApi;
use ZcCenter\ThinkPHP\Api\Auth;
use ZcCenter\ThinkPHP\Api\Ping;
use ZcCenter\ThinkPHP\Api\Question;
use ZcCenter\ThinkPHP\Api\QuestionBank;
use ZcCenter\ThinkPHP\Api\User;

/**
 * ThinkPHP Facade。
 *
 * @method static Ping ping()
 * @method static Auth auth()
 * @method static User user()
 * @method static Question question()
 * @method static QuestionBank questionBank()
 * @method static bool isReportEnabled()
 * @method static array resolveReportBank(array $override = [])
 * @method static AbstractApi api(object|string<AbstractApi> $apiClass)
 * @method static Response post(string $path, array $payload = [], array $query = [])
 * @method static Response get(string $path, array $query = [])
 * @method static Response request(string $method, string $path, array $payload = [], array $query = [])
 */
class ZcCenter extends ThinkFacade
{
    protected static function getFacadeClass(): string
    {
        return Client::class;
    }
}
