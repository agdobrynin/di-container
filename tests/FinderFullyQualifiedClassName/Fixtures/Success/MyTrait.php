<?php

declare(strict_types=1);

namespace Tests\FinderFullyQualifiedClassName\Fixtures\Success;

trait MyTrait
{
    private array $tokens = [];

    public function getTokens(): array
    {
        return $this->tokens;
    }

    public function setTokens(array $tokens): void
    {
        $this->tokens = $tokens;
    }
}
