# ZC Center ThinkPHP SDK

适用于 ThinkPHP 6.1/8.x 的中台 SAPI 服务端 SDK，提供 HMAC-SHA256 请求签名、AES-256-GCM 加解密、响应验签和现有业务接口封装。

## 环境要求

- PHP 8.1+
- OpenSSL 扩展
- JSON 扩展
- ThinkPHP 6.1 或 8.x
- Guzzle 7.x

`app_secret` 只能保存在生态应用服务端，禁止写入前端代码、客户端安装包或日志。

## 安装

SDK 尚未发布到 Packagist。生态 ThinkPHP 项目可用 **本地 Path 引用** 安装。

在生态项目根目录的 `composer.json` 中增加 Path Repository，并声明依赖：

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../zc-center/sdk/thinkphp"
    }
  ],
  "require": {
    "zc-center/thinkphp-sdk": "@dev"
  }
}
```

将 `url` 改为相对生态项目根目录到本仓库 `sdk/thinkphp` 的实际路径，然后执行：

```bash
composer update zc-center/thinkphp-sdk
```

Composer 会通过 symlink（或拷贝）把包装入 `vendor/zc-center/thinkphp-sdk`。本包在 `composer.json` 的 `extra.think.services` 中声明了服务提供者，ThinkPHP 会自动注册，一般无需手动配置。

本机联调适合 Path 引用；CI / 部署机拿不到这份源码时，应改用私有 Git（VCS Repository）或内网 Composer 源，不要只依赖本机 path。

## 配置

在生态项目 `.env` 中添加：

```ini
[ZC_CENTER]
BASE_URL=https://zc-center.example.com
APP_KEY=中台分配的app_key
APP_SECRET=中台创建应用时仅展示一次的app_secret
ENCRYPTION=true
TIMEOUT=10
CONNECT_TIMEOUT=3
VERIFY_SSL=true
```

SDK 服务提供者会自动注册，并将默认配置与生态项目的 `config/zc_center.php` 合并。如需覆盖默认配置，可创建：

```php
<?php

return [
    'base_url' => env('ZC_CENTER.BASE_URL', ''),
    'app_key' => env('ZC_CENTER.APP_KEY', ''),
    'app_secret' => env('ZC_CENTER.APP_SECRET', ''),
    'encryption' => (bool) env('ZC_CENTER.ENCRYPTION', true),
    'timeout' => (float) env('ZC_CENTER.TIMEOUT', 10),
    'connect_timeout' => (float) env('ZC_CENTER.CONNECT_TIMEOUT', 3),
    'verify_ssl' => (bool) env('ZC_CENTER.VERIFY_SSL', true),
];
```

中台联调环境关闭了 `SAPI_ENCRYPTION_ENABLED` 时，生态项目必须同步设置 `ENCRYPTION=false`。生产环境双方都必须开启加密。

## 使用

### 依赖注入

```php
use ZcCenter\ThinkPHP\Client;

class CenterController
{
    public function ping(Client $center): array
    {
        return $center->ping()->send('hello')->payload();
    }
}
```

### Facade

```php
use ZcCenter\ThinkPHP\ZcCenter;

$response = ZcCenter::ping()->send('hello');
$data = $response->data();
```

`ZcCenter` 继承 ThinkPHP 原生 `think\Facade`，实际代理容器中单例绑定的 `Client`。

### 注册中台用户

```php
$response = $center->user()->register(
    mobile: '13800138000',
    countryCode: '+86',
    nickname: '示例用户',
    avatar: 'https://example.com/avatar.png',
);

$uuid = $response->data()['user']['uuid'];
```

### 来源应用申请 Ticket

```php
$response = $center->auth()->issueTicket(
    uuid: '550e8400-e29b-41d4-a716-446655440000',
    targetAppCode: 'product-b',
);

$ticket = $response->data()['ticket'];
```

### 目标应用兑换 Ticket

```php
$response = $center->auth()->exchangeTicket($ticket);
$user = $response->data()['user'];

// 使用中台永久 UUID 查找或创建当前生态产品的本地用户，再签发本产品 Token。
$uuid = $user['uuid'];
```

## 扩展生态产品接口

底层 `Client` 只负责签名、加密、请求和响应验证，标准接口集中在 `Api\Sapi`。不同生态产品可以定义自己的接口集合，不需要修改 SDK 核心：

```php
<?php

namespace app\service\center;

use ZcCenter\ThinkPHP\Api\AbstractApi;
use ZcCenter\ThinkPHP\Response;

final class ClassroomApi extends AbstractApi
{
    public function syncCourse(string $courseId, string $title): Response
    {
        return $this->post('/sapi/classroom/course/sync', [
            'course_id' => $courseId,
            'title' => $title,
        ]);
    }
}
```

通过依赖注入使用：

```php
/** @var ClassroomApi $classroom */
$classroom = $center->api(ClassroomApi::class);
$response = $classroom->syncCourse('course-1001', '示例课程');
```

通过 Facade 使用：

```php
use ZcCenter\ThinkPHP\ZcCenter;

/** @var ClassroomApi $classroom */
$classroom = ZcCenter::api(ClassroomApi::class);
$response = $classroom->syncCourse('course-1001', '示例课程');
```

对于只有一次调用、不值得建立接口类的情况，仍可使用底层方法：

```php
$response = $center->post('/sapi/example', ['field' => 'value']);
```

## 异常处理

```php
use ZcCenter\ThinkPHP\Exception\ApiException;
use ZcCenter\ThinkPHP\Exception\SignatureException;
use ZcCenter\ThinkPHP\Exception\TransportException;

try {
    $response = $center->auth()->exchangeTicket($ticket);
} catch (ApiException $exception) {
    // 中台业务错误，例如 Ticket 已使用或过期。
    $businessCode = $exception->businessCode();
    $httpStatus = $exception->httpStatus();
    $message = $exception->getMessage();
} catch (SignatureException $exception) {
    // 响应可能被篡改、密钥不一致，或中间代理修改了 Body。
} catch (TransportException $exception) {
    // 网络超时、DNS、TLS 或响应格式错误。
}
```

认证阶段的错误响应可能没有响应签名，此时 `ApiException::signatureVerified()` 返回 `false`，不得将其中的数据作为可信用户身份使用。

## 安全行为

- 每次请求自动生成 24 位 Base64URL nonce。
- 请求签名覆盖方法、路径、排序后的查询参数、时间戳、nonce 和原始 Body。
- 加密使用 `SHA-256(app_secret)` 派生 AES-256-GCM 密钥。
- 加密响应先验签、后解密。
- 明文联调响应基于原始 Body 验签，验签前不会重新编码 JSON。
- 默认启用 TLS 证书验证，生产环境禁止设置 `VERIFY_SSL=false`。

## 自测

在中台项目根目录执行：

```bash
php sdk/thinkphp/tests/run.php
```
