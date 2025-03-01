<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\Import\SubDirectory;

use Tests\DefinitionsLoader\Fixtures\Import\TokenInterface;

final class One implements TokenInterface
{
    public function __construct(private string $token = 'qux') {}

    public function getToken(): string
    {
        return $this->token;
    }
}
