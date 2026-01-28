<?php

declare(strict_types=1);

namespace Tests\Integration\Fixtures\ResolveExcludedIds;

use Kaspi\DiContainer\Attributes\AutowireExclude;

#[AutowireExclude]
final class Person
{
    public function __construct(
        public readonly string $firstName,
        public string $lastName,
        public int $age,
    ) {}
}
