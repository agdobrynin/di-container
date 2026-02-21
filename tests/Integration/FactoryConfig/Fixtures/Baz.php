<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryConfig\Fixtures;

final class Baz
{
    public function __construct(
        public readonly ApiClientInterface $apiClient
    ) {}
}
