<?php

declare(strict_types=1);

namespace Tests\Unit\Definition\Fixtures;

class SimpleServiceWithArgument
{
    public function __construct(private string $token) {}

    public function getToken(): string
    {
        return $this->token;
    }

    public function getRefreshToken(): array
    {
        return ['token' => $this->token, 'refreshToken' => 'qqq-ccc-fff'];
    }
}
