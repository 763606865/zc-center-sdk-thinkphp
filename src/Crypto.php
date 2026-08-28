<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP;

use JsonException;
use ZcCenter\ThinkPHP\Exception\CryptoException;

final class Crypto
{
    /** @throws JsonException */
    public function encrypt(array $payload, string $appSecret, string $aad): array
    {
        $plaintext = $this->jsonEncode($payload);
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $this->key($appSecret),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad,
            16
        );

        if ($ciphertext === false) {
            throw new CryptoException('SAPI请求数据加密失败');
        }

        return [
            'algorithm' => 'AES-256-GCM',
            'ciphertext' => base64_encode($ciphertext),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
        ];
    }

    /** @throws JsonException */
    public function decrypt(array $envelope, string $appSecret, string $aad): array
    {
        foreach (['ciphertext', 'iv', 'tag'] as $field) {
            if (!is_string($envelope[$field] ?? null) || $envelope[$field] === '') {
                throw new CryptoException("SAPI加密响应缺少字段：{$field}");
            }
        }

        $ciphertext = base64_decode($envelope['ciphertext'], true);
        $iv = base64_decode($envelope['iv'], true);
        $tag = base64_decode($envelope['tag'], true);
        if ($ciphertext === false || $iv === false || $tag === false || strlen($iv) !== 12 || strlen($tag) !== 16) {
            throw new CryptoException('SAPI加密响应格式错误');
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->key($appSecret),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );
        if ($plaintext === false) {
            throw new CryptoException('SAPI响应解密或完整性验证失败');
        }

        $payload = json_decode($plaintext, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            throw new CryptoException('SAPI解密响应不是JSON对象');
        }

        return $payload;
    }

    public function canonicalRequest(
        string $method,
        string $path,
        array $query,
        string $timestamp,
        string $nonce,
        string $rawBody
    ): string {
        ksort($query);

        return implode("\n", [
            strtoupper($method),
            '/' . ltrim($path, '/'),
            http_build_query($query, '', '&', PHP_QUERY_RFC3986),
            $timestamp,
            $nonce,
            hash('sha256', $rawBody),
        ]);
    }

    public function sign(string $canonical, string $appSecret): string
    {
        return hash_hmac('sha256', $canonical, $appSecret);
    }

    public function aad(string $appKey, string $timestamp, string $nonce): string
    {
        return implode("\n", [$appKey, $timestamp, $nonce]);
    }

    public function verifyEncryptedResponse(
        array $envelope,
        string $signature,
        string $timestamp,
        string $nonce,
        string $appSecret
    ): bool {
        foreach (['iv', 'tag', 'ciphertext'] as $field) {
            if (!is_string($envelope[$field] ?? null)) {
                return false;
            }
        }

        $canonical = implode("\n", [
            $timestamp,
            $nonce,
            $envelope['iv'],
            $envelope['tag'],
            $envelope['ciphertext'],
        ]);

        return $signature !== '' && hash_equals($this->sign($canonical, $appSecret), strtolower($signature));
    }

    public function verifyPlaintextResponse(
        string $rawBody,
        string $signature,
        string $timestamp,
        string $nonce,
        string $appSecret
    ): bool {
        $canonical = implode("\n", [$timestamp, $nonce, hash('sha256', $rawBody)]);

        return $signature !== '' && hash_equals($this->sign($canonical, $appSecret), strtolower($signature));
    }

    /** @throws JsonException */
    public function jsonEncode(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public function nonce(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }

    private function key(string $appSecret): string
    {
        return hash('sha256', $appSecret, true);
    }
}
