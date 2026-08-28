<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP;

final class Response
{
    public function __construct(
        private readonly int $statusCode,
        private readonly array $payload,
        private readonly array $headers,
        private readonly string $rawBody,
    ) {
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function data(): mixed
    {
        return $this->payload['data'] ?? null;
    }

    public function message(): string
    {
        return (string) ($this->payload['msg'] ?? '');
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function rawBody(): string
    {
        return $this->rawBody;
    }
}
