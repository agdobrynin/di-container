<?php

declare(strict_types=1);

namespace Tests\FinderClass\Fixtures\Success;

interface QueueInterface
{
    public function push(mixed $item): void;
}
