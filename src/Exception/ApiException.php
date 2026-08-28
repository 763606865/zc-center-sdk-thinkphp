<?php

declare(strict_types=1);

namespace ZcCenter\ThinkPHP\Exception;

class ApiException extends SapiException
{
    public function __construct(
        string $message,
        private readonly int $businessCode,
        private readonly int $httpStatus,
        private readonly ?array $responseData = null,
        private readonly bool $signatureVerified = true,
    ) {
        parent::__construct($message, $businessCode);
    }

    public function businessCode(): int
    {
        return $this->businessCode;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public function responseData(): ?array
    {
        return $this->responseData;
    }

    public function signatureVerified(): bool
    {
        return $this->signatureVerified;
    }
}
