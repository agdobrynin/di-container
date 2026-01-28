<?php

declare(strict_types=1);

namespace Tests\Integration\Fixtures\ResolveExcludedIds;

use Kaspi\DiContainer\Attributes\DiFactory;

final class Other
{
    public function __construct(
        #[DiFactory(DiFactoryPerson::class)]
        public readonly Person $person
    ) {}
}
