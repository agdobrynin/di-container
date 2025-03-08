<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\Import;

use Kaspi\DiContainer\Attributes\Service;

#[Service(Two::class)]
interface TokenInterface
{
    public function getToken(): string;
}
