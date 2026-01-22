<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys\Fixtures;

final class TaggedService
{
    public function __construct(public iterable $items) {}
}
