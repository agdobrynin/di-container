<?php

declare(strict_types=1);

namespace Tests\Integration\ArgumentsViaAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

final class Bar implements QuxInterface
{
    public function __construct(
        #[Inject(Bat::class)]
        public readonly QuuuxInterface $quuux,
    ) {}
}
