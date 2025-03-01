<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\Import;

final class One implements TokenInterface
{
    public function __construct(private string $token) {}

    public function getToken(): string
    {
        return $this->token;
    }
}
