<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

class DependenciesByReference
{
    public function __construct(
        public \ArrayIterator $dependencies1,
        public \ArrayIterator $dependencies2,
    ) {}
}
