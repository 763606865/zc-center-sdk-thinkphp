<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use ZcCenter\ThinkPHP\Api\AbstractApi;
use ZcCenter\ThinkPHP\Api\Auth;
use ZcCenter\ThinkPHP\Api\Dict;
use ZcCenter\ThinkPHP\Api\Enterprise;
use ZcCenter\ThinkPHP\Api\Organization;
use ZcCenter\ThinkPHP\Api\ExamNotice;
use ZcCenter\ThinkPHP\Api\ExamPosition;
use ZcCenter\ThinkPHP\Api\Ping;
use ZcCenter\ThinkPHP\Api\Question;
use ZcCenter\ThinkPHP\Api\QuestionBank;
use ZcCenter\ThinkPHP\Api\Job;
use ZcCenter\ThinkPHP\Api\JobBank;
use ZcCenter\ThinkPHP\Api\Resume;
use ZcCenter\ThinkPHP\Api\User;
use ZcCenter\ThinkPHP\Exception\ApiException;
use ZcCenter\ThinkPHP\Exception\SapiException;
use ZcCenter\ThinkPHP\Exception\SignatureException;
use ZcCenter\ThinkPHP\Exception\TransportException;

final class Client
{
    private readonly string $baseUrl;
    private readonly string $appKey;
    private readonly string $appSecret;
    private readonly bool $encryption;
    private readonly bool $debug;
    private readonly bool $reportEnabled;
    private readonly string $reportBankUuid;
    private readonly string $reportBankCode;
    private readonly ClientInterface $http;
    private readonly Crypto $crypto;
    /** @var (callable(string, array<string,mixed>): void)|null */
    private $logger;
    /** @var array<class-string<AbstractApi>, AbstractApi> */
    private array $apis = [];

    public function __construct(array $config, ?ClientInterface $http = null, ?Crypto $crypto = null)
    {
        $this->baseUrl = rtrim(trim((string) ($config['base_url'] ?? '')), '/');
        $this->appKey = trim((string) ($config['app_key'] ?? ''));
        $this->appSecret = (string) ($config['app_secret'] ?? '');
        $this->encryption = (bool) ($config['encryption'] ?? true);
        $this->debug = (bool) ($config['debug'] ?? false);
        $this->reportEnabled = (bool) ($config['report_enabled'] ?? false);
        $this->reportBankUuid = strtolower(trim((string) ($config['report_bank_uuid'] ?? '')));
        $this->reportBankCode = trim((string) ($config['report_bank_code'] ?? ''));
        $this->logger = $this->resolveLogger($config['logger'] ?? null);

        if ($this->baseUrl === '' || !filter_var($this->baseUrl, FILTER_VALIDATE_URL)) {
            throw new SapiException('ZC Center SDK的base_url配置无效');
        }
        if ($this->appKey === '' || $this->appSecret === '') {
            throw new SapiException('ZC Center SDK的app_key或app_secret未配置');
        }
        if ($this->reportEnabled && $this->reportBankUuid === '' && $this->reportBankCode === '') {
            throw new SapiException('开启题目上报时必须配置 report_bank_uuid 或 report_bank_code');
        }

        $this->http = $http ?? new HttpClient([
            'timeout' => (float) ($config['timeout'] ?? 10),
            'connect_timeout' => (float) ($config['connect_timeout'] ?? 3),
            'verify' => (bool) ($config['verify_ssl'] ?? true),
            'http_errors' => false,
        ]);
        $this->crypto = $crypto ?? new Crypto();
    }

    /**
     * 是否开启题目上报。
     */
    public function isReportEnabled(): bool
    {
        return $this->reportEnabled;
    }

    /**
     * 解析上报目标题库：调用方显式传入优先，否则使用配置。
     *
     * @param array{bank_uuid?: string|null, bank_code?: string|null} $override
     * @return array{bank_uuid?: string, bank_code?: string}
     */
    public function resolveReportBank(array $override = []): array
    {
        if (!$this->reportEnabled) {
            throw new SapiException('题目上报未开启，请在配置中设置 report_enabled=true');
        }

        $overrideUuid = strtolower(trim((string) ($override['bank_uuid'] ?? '')));
        $overrideCode = trim((string) ($override['bank_code'] ?? ''));
        $hasOverride = $overrideUuid !== '' || $overrideCode !== '';

        $uuid = $hasOverride ? $overrideUuid : $this->reportBankUuid;
        $code = $hasOverride ? $overrideCode : $this->reportBankCode;

        if ($uuid === '' && $code === '') {
            throw new SapiException('请配置或传入上报目标题库 bank_uuid / bank_code');
        }

        $bank = [];
        if ($uuid !== '') {
            $bank['bank_uuid'] = $uuid;
        }
        if ($code !== '') {
            $bank['bank_code'] = $code;
        }

        return $bank;
    }

    public function ping(): Ping
    {
        /** @var Ping */
        return $this->api(Ping::class);
    }

    public function auth(): Auth
    {
        /** @var Auth */
        return $this->api(Auth::class);
    }

    public function user(): User
    {
        /** @var User */
        return $this->api(User::class);
    }

    public function enterprise(): Enterprise
    {
        /** @var Enterprise */
        return $this->api(Enterprise::class);
    }

    public function organization(): Organization
    {
        /** @var Organization */
        return $this->api(Organization::class);
    }

    public function examNotice(): ExamNotice
    {
        /** @var ExamNotice */
        return $this->api(ExamNotice::class);
    }

    public function examPosition(): ExamPosition
    {
        /** @var ExamPosition */
        return $this->api(ExamPosition::class);
    }

    public function resume(): Resume
    {
        /** @var Resume */
        return $this->api(Resume::class);
    }

    public function dict(): Dict
    {
        /** @var Dict */
        return $this->api(Dict::class);
    }

    public function question(): Question
    {
        /** @var Question */
        return $this->api(Question::class);
    }

    public function questionBank(): QuestionBank
    {
        /** @var QuestionBank */
        return $this->api(QuestionBank::class);
    }

    public function job(): Job
    {
        /** @var Job */
        return $this->api(Job::class);
    }

    public function jobBank(): JobBank
    {
        /** @var JobBank */
        return $this->api(JobBank::class);
    }

    /**
     * 获取生态产品自定义的接口集合。
     *
     * @template T of AbstractApi
     * @param class-string<T> $apiClass
     * @return T
     */
    public function api(string $apiClass): AbstractApi
    {
        if (!is_a($apiClass, AbstractApi::class, true)) {
            throw new SapiException("自定义接口类必须继承" . AbstractApi::class);
        }

        return $this->apis[$apiClass] ??= new $apiClass($this);
    }

    public function get(string $path, array $query = []): Response
    {
        return $this->request('GET', $path, [], $query);
    }

    public function post(string $path, array $payload = [], array $query = []): Response
    {
        return $this->request('POST', $path, $payload, $query);
    }

    public function request(string $method, string $path, array $payload = [], array $query = []): Response
    {
        $path = '/' . ltrim($path, '/');
        if (!str_starts_with($path, '/sapi/')) {
            throw new SapiException('SAPI请求路径必须以/sapi/开头');
        }

        $timestamp = (string) time();
        $nonce = $this->crypto->nonce();
        $aad = $this->crypto->aad($this->appKey, $timestamp, $nonce);

        try {
            $body = $this->crypto->jsonEncode(
                $this->encryption ? $this->crypto->encrypt($payload, $this->appSecret, $aad) : $payload
            );
        } catch (JsonException $exception) {
            throw new SapiException('SAPI请求JSON编码失败', 0, $exception);
        }

        $canonical = $this->crypto->canonicalRequest($method, $path, $query, $timestamp, $nonce, $body);
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-App-Key' => $this->appKey,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'X-Signature' => $this->crypto->sign($canonical, $this->appSecret),
            'X-Encrypted' => $this->encryption ? '1' : '0',
        ];

        $url = $this->baseUrl . $path;
        $this->writeLog('SAPI request', [
            'method' => strtoupper($method),
            'url' => $url,
            'query' => $query,
            'request_headers' => $headers,
            'request_params' => $payload,
            'request_body' => $this->decodeJsonOrRaw($body),
        ]);

        try {
            $httpResponse = $this->http->request($method, $url, [
                'headers' => $headers,
                'query' => $query,
                'body' => $body,
                'http_errors' => false,
            ]);
        } catch (GuzzleException $exception) {
            $this->writeLog('SAPI request transport error', [
                'method' => strtoupper($method),
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);
            throw new TransportException('SAPI网络请求失败：' . $exception->getMessage(), 0, $exception);
        }

        return $this->parseResponse($httpResponse, $timestamp, $nonce, $aad);
    }

    private function parseResponse(
        ResponseInterface $response,
        string $timestamp,
        string $nonce,
        string $aad
    ): Response {
        $rawBody = (string) $response->getBody();
        $statusCode = $response->getStatusCode();
        $responseHeaders = $this->normalizeHeaders($response->getHeaders());
        $signature = trim($response->getHeaderLine('X-Response-Signature'));
        $encrypted = $response->getHeaderLine('X-Encrypted') === '1';

        $this->writeLog('SAPI response', [
            'http_status' => $statusCode,
            'response_headers' => $responseHeaders,
            'response_body' => $this->decodeJsonOrRaw($rawBody),
        ]);

        try {
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new TransportException('SAPI响应不是有效JSON', 0, $exception);
        }
        if (!is_array($decoded)) {
            throw new TransportException('SAPI响应必须是JSON对象');
        }

        if ($signature === '') {
            if ($statusCode >= 400) {
                throw $this->apiException($decoded, $statusCode, false);
            }
            throw new SignatureException('SAPI响应缺少X-Response-Signature');
        }

        if ($encrypted) {
            if (!$this->crypto->verifyEncryptedResponse($decoded, $signature, $timestamp, $nonce, $this->appSecret)) {
                throw new SignatureException('SAPI加密响应签名验证失败');
            }
            try {
                $payload = $this->crypto->decrypt($decoded, $this->appSecret, $aad);
            } catch (Throwable $exception) {
                if ($exception instanceof SapiException) {
                    throw $exception;
                }
                throw new SapiException('SAPI响应解密失败', 0, $exception);
            }
        } else {
            if (!$this->crypto->verifyPlaintextResponse($rawBody, $signature, $timestamp, $nonce, $this->appSecret)) {
                throw new SignatureException('SAPI明文响应签名验证失败');
            }
            $payload = $decoded;
        }

        $this->writeLog('SAPI response payload', [
            'http_status' => $statusCode,
            'response_payload' => $payload,
        ]);

        if ($statusCode >= 400 || (int) ($payload['code'] ?? 0) !== 0) {
            throw $this->apiException($payload, $statusCode, true);
        }

        return new Response($statusCode, $payload, $response->getHeaders(), $rawBody);
    }

    private function apiException(array $payload, int $httpStatus, bool $signatureVerified): ApiException
    {
        return new ApiException(
            (string) ($payload['msg'] ?? 'SAPI接口调用失败'),
            (int) ($payload['code'] ?? 0),
            $httpStatus,
            is_array($payload['data'] ?? null) ? $payload['data'] : null,
            $signatureVerified
        );
    }

    /**
     * @param mixed $logger
     * @return (callable(string, array<string,mixed>): void)|null
     */
    private function resolveLogger(mixed $logger): mixed
    {
        if (is_callable($logger)) {
            return $logger;
        }

        return null;
    }

    /**
     * @param array<string,mixed> $context
     */
    private function writeLog(string $message, array $context = []): void
    {
        if (!$this->debug) {
            return;
        }

        if ($this->logger !== null) {
            ($this->logger)($message, $context);
            return;
        }

        $encoded = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        error_log('[ZcCenter SDK] ' . $message . ' ' . ($encoded === false ? '{}' : $encoded));
    }

    /**
     * @param array<string, list<string>> $headers
     * @return array<string, string|list<string>>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $name => $values) {
            $normalized[$name] = count($values) === 1 ? $values[0] : $values;
        }

        return $normalized;
    }

    private function decodeJsonOrRaw(string $raw): mixed
    {
        if ($raw === '') {
            return '';
        }
        try {
            return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $raw;
        }
    }
}
