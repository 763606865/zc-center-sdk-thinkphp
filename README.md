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
DEBUG=false
; 题目上报（可选）：开启后 report / reportBatch 才会可用；目标题库 uuid / code 二选一
REPORT_ENABLED=false
REPORT_BANK_UUID=
REPORT_BANK_CODE=
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
    'debug' => (bool) env('ZC_CENTER.DEBUG', false),
    'report_enabled' => (bool) env('ZC_CENTER.REPORT_ENABLED', false),
    'report_bank_uuid' => (string) env('ZC_CENTER.REPORT_BANK_UUID', ''),
    'report_bank_code' => (string) env('ZC_CENTER.REPORT_BANK_CODE', ''),
];
```

`report_enabled=true` 时必须配置 `report_bank_uuid` 或 `report_bank_code`（二选一，也可都配；请求时 uuid 优先）。关闭上报时调用 `report` / `reportBatch` 会抛出 `SapiException`。
中台联调环境关闭了 `SAPI_ENCRYPTION_ENABLED` 时，生态项目必须同步设置 `ENCRYPTION=false`。生产环境双方都必须开启加密。

设置 `DEBUG=true` 后，每次 SAPI 调用会写入日志（ThinkPHP 下走 `Log::info`，否则 `error_log`），内容包括：

- 请求：method、url、query、request_headers、request_params（明文业务参数）、request_body（实际发送 Body，加密时为密文信封）
- 响应：http_status、response_headers、response_body（原始 Body）、response_payload（验签解密后的业务 JSON）

生产环境请保持 `DEBUG=false`，避免日志泄露业务数据与签名头。

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

### 企业上报与职工

```php
use ZcCenter\ThinkPHP\Api\Enterprise;

$reported = $center->enterprise()->report([
    'name' => '示例科技有限公司',
    'code' => 'example_tech',
    'credit_code' => '91110000MA01234567',
    'admin_mobile' => '13800138000',
])->data();

$center->enterprise()->addMember([
    'enterprise_uuid' => $reported['enterprise']['uuid'],
    'mobile' => '13900139000',
    'role' => Enterprise::ROLE_MEMBER,
]);

$center->enterprise()->removeMember([
    'enterprise_uuid' => $reported['enterprise']['uuid'],
    'mobile' => '13900139000',
]);
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

### 获取题目列表

```php
use ZcCenter\ThinkPHP\Api\Question;

$response = $center->question()->list([
    'page' => 1,
    'page_size' => 20,
    'bank_uuid' => '3aae1b52-8fca-42c6-9bd6-7bd4901ceb66', // 可选
    'type' => Question::TYPE_SINGLE,                 // 可选：1单选 2多选 3判断 4填空 5简答
    'difficulty' => Question::DIFFICULTY_MEDIUM,     // 可选：1易 2中 3难
    'tag_uuids' => [],                               // 可选
    'updated_since' => 0,                            // 可选，增量同步
    'include_answer' => false,                       // true 时返回标准答案与解析
]);

$list = $response->data()['list'];
$total = $response->data()['total'];
$engine = $response->data()['engine']; // elasticsearch | mysql
```

### 搜索题目

```php
$response = $center->question()->search([
    'keyword' => '导数',
    'page' => 1,
    'page_size' => 20,
    'bank_uuid' => null, // null 会被 SDK 自动省略
    'include_answer' => false,
]);
$hits = $response->data()['list'];
```

### 获取题目详情

```php
$response = $center->question()->detail(
    uuid: 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx',
    includeAnswer: false,
);
$question = $response->data()['question'];
```

### 批量拉取题目

```php
$response = $center->question()->batch(
    uuids: ['uuid-1', 'uuid-2'],
    includeAnswer: false,
);
$list = $response->data()['list'];
```

### 题库列表 / 详情

```php
$banks = $center->questionBank()->list([
    'page' => 1,
    'page_size' => 20,
    'keyword' => '数学',
])->data()['list'];

$bank = $center->questionBank()
    ->detail('3aae1b52-8fca-42c6-9bd6-7bd4901ceb66')
    ->data()['bank'];
// $bank['question_count'] 为该库已发布题目数
```

### 上报题目（按题干去重）

仅可向 **当前应用归属** 的题库上报。同一题库内规范化题干（去 HTML、压缩空白）相同则返回已有题目，不重复创建。

需先开启 `report_enabled`，并配置目标题库。默认使用配置中的题库；也可在调用时传入 `bank_uuid` / `bank_code` 覆盖。

```php
// 推荐：题库写在配置里，业务代码只传题目字段
if (!$center->isReportEnabled()) {
    // 未开启上报，跳过同步
}

$response = $center->question()->report([
    'type' => Question::TYPE_SINGLE,
    'difficulty' => Question::DIFFICULTY_MEDIUM,
    'stem' => '1+1等于多少？',
    'options' => [
        ['key' => 'A', 'content' => '1'],
        ['key' => 'B', 'content' => '2'],
    ],
    'answer' => 'B',
    'analysis' => '基础运算',
    'score' => 1,
]);

$action = $response->data()['action']; // created | exists
$question = $response->data()['question'];
```

### 批量上报题目

```php
$response = $center->question()->reportBatch([
    [
        'external_id' => 'local-1001',
        'type' => Question::TYPE_JUDGE,
        'stem' => '地球是圆的。',
        'answer' => true,
    ],
    [
        'external_id' => 'local-1002',
        'type' => Question::TYPE_SINGLE,
        'stem' => '1+1等于多少？',
        'options' => [
            ['key' => 'A', 'content' => '1'],
            ['key' => 'B', 'content' => '2'],
        ],
        'answer' => 'B',
    ],
]);
// 如需临时覆盖目标题库：->reportBatch($items, ['bank_code' => 'math_basic'])

$data = $response->data();
// created / exists / failed + results[]
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
