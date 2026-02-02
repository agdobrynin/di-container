<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryConfig\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

final class BazAttr
{
    public function __construct(
        #[DiFactory([ApiClientFactory::class, 'createApiV2'])]
        public readonly ApiClientInterface $apiClient
    ) {}
}
