<?php

declare(strict_types=1);

namespace Tests\FinderFullyQualifiedClassName\Fixtures\Success;

interface QueueInterface
{
    public function push(mixed $item): void;
}
