<?php

declare(strict_types=1);

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response as HttpResponse;
use Psr\Http\Message\RequestInterface;
use ZcCenter\ThinkPHP\Api\AbstractApi;
use ZcCenter\ThinkPHP\Client;
use ZcCenter\ThinkPHP\Crypto;
use ZcCenter\ThinkPHP\Response;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'ZcCenter\\ThinkPHP\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

final class TestProductApi extends AbstractApi
{
    public function example(): Response
    {
        return $this->post('/sapi/example', ['echo' => 'hello']);
    }

    public function find(string $id): Response
    {
        return $this->get('/sapi/example/detail', ['id' => $id]);
    }
}

$secret = 'sdk-test-secret';
$appKey = 'sdk-test-app-key';
$crypto = new Crypto();

$canonical = $crypto->canonicalRequest(
    'post',
    '/sapi/ping',
    ['z' => '最后', 'a' => 'first value'],
    '1787823238',
    'g3N4RKzB9QMHYVqE4-ug_SDl',
    '{"echo":"hello"}'
);
check(
    $canonical === "POST\n/sapi/ping\na=first%20value&z=%E6%9C%80%E5%90%8E\n1787823238\ng3N4RKzB9QMHYVqE4-ug_SDl\n" . hash('sha256', '{"echo":"hello"}'),
    'Canonical Request构造错误'
);

$aad = $crypto->aad($appKey, '1787823238', 'g3N4RKzB9QMHYVqE4-ug_SDl');
$envelope = $crypto->encrypt(['hello' => '世界'], $secret, $aad);
check($crypto->decrypt($envelope, $secret, $aad) === ['hello' => '世界'], 'AES-GCM往返测试失败');

$makeHttp = static function (bool $encrypted) use ($secret, $crypto): HttpClient {
    $handler = static function (RequestInterface $request, array $options) use ($encrypted, $secret, $crypto) {
        $timestamp = $request->getHeaderLine('X-Timestamp');
        $nonce = $request->getHeaderLine('X-Nonce');
        $appKey = $request->getHeaderLine('X-App-Key');
        $requestBody = (string) $request->getBody();
        parse_str($request->getUri()->getQuery(), $query);
        $canonical = $crypto->canonicalRequest(
            $request->getMethod(),
            $request->getUri()->getPath(),
            $query,
            $timestamp,
            $nonce,
            $requestBody
        );
        check(
            hash_equals($crypto->sign($canonical, $secret), $request->getHeaderLine('X-Signature')),
            '客户端请求签名错误'
        );

        $payload = ['code' => 0, 'msg' => 'pong', 'data' => ['echo' => 'hello']];
        if ($encrypted) {
            $responseEnvelope = $crypto->encrypt($payload, $secret, $crypto->aad($appKey, $timestamp, $nonce));
            $rawBody = $crypto->jsonEncode($responseEnvelope);
            $responseCanonical = implode("\n", [
                $timestamp,
                $nonce,
                $responseEnvelope['iv'],
                $responseEnvelope['tag'],
                $responseEnvelope['ciphertext'],
            ]);
        } else {
            $rawBody = $crypto->jsonEncode($payload);
            $responseCanonical = implode("\n", [$timestamp, $nonce, hash('sha256', $rawBody)]);
        }

        return Create::promiseFor(new HttpResponse(200, [
            'Content-Type' => 'application/json',
            'X-Encrypted' => $encrypted ? '1' : '0',
            'X-Response-Signature' => $crypto->sign($responseCanonical, $secret),
        ], $rawBody));
    };

    return new HttpClient(['handler' => $handler]);
};

foreach ([true, false] as $encrypted) {
    $client = new Client([
        'base_url' => 'https://center.example.com',
        'app_key' => $appKey,
        'app_secret' => $secret,
        'encryption' => $encrypted,
    ], $makeHttp($encrypted), $crypto);

    check($client->ping()->send('hello')->data() === ['echo' => 'hello'], ($encrypted ? '加密' : '明文') . '请求测试失败');
    check($client->ping() === $client->ping(), 'Ping接口对象未被复用');
    check($client->auth() === $client->auth(), 'Auth接口对象未被复用');
    check($client->user() === $client->user(), 'User接口对象未被复用');
    check($client->question() === $client->question(), 'Question接口对象未被复用');
    check($client->questionBank() === $client->questionBank(), 'QuestionBank接口对象未被复用');
    check(
        $client->auth()->issueTicket('550e8400-e29b-41d4-a716-446655440000', 'product-b')->data() === ['echo' => 'hello'],
        'Auth接口调用失败'
    );
    check(
        $client->user()->register('13800138000')->data() === ['echo' => 'hello'],
        'User接口调用失败'
    );
    check(
        $client->question()->list(['page' => 1])->data() === ['echo' => 'hello'],
        'Question列表接口调用失败'
    );
    check(
        $client->question()->search(['keyword' => '导数', 'page' => 1])->data() === ['echo' => 'hello'],
        'Question搜索接口调用失败'
    );
    check(
        $client->question()->detail('550e8400-e29b-41d4-a716-446655440000')->data() === ['echo' => 'hello'],
        'Question详情接口调用失败'
    );
    check(
        $client->question()->batch(['550e8400-e29b-41d4-a716-446655440000'], true)->data() === ['echo' => 'hello'],
        'Question批量接口调用失败'
    );
    check(
        $client->question()->report([
            'bank_uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'type' => 1,
            'stem' => '1+1=?',
            'options' => [['key' => 'A', 'content' => '2']],
            'answer' => 'A',
        ])->data() === ['echo' => 'hello'],
        'Question上报接口调用失败'
    );
    check(
        $client->question()->reportBatch(
            ['bank_code' => 'math_basic'],
            [['type' => 3, 'stem' => '地球是圆的', 'answer' => true]]
        )->data() === ['echo' => 'hello'],
        'Question批量上报接口调用失败'
    );
    check(
        $client->questionBank()->list(['keyword' => '数学'])->data() === ['echo' => 'hello'],
        'QuestionBank列表接口调用失败'
    );
    check(
        $client->questionBank()->detail('550e8400-e29b-41d4-a716-446655440000')->data() === ['echo' => 'hello'],
        'QuestionBank详情接口调用失败'
    );

    /** @var TestProductApi $customApi */
    $customApi = $client->api(TestProductApi::class);
    check($customApi->example()->data() === ['echo' => 'hello'], '自定义生态接口扩展测试失败');
    check($customApi->find('detail-1001')->data() === ['echo' => 'hello'], '自定义GET接口扩展测试失败');
    check($customApi === $client->api(TestProductApi::class), '自定义接口对象未被复用');
}

echo "ZC Center ThinkPHP SDK tests passed.\n";
