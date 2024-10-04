<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;

class InjectSimpleArgumentWithSharedFalse
{
    public function __construct(
        #[Inject(
            arguments: ['array' => ['first' => '🥇', 'second' => '🥈']],
            isShared: false
        )]
        protected \ArrayIterator $arrayIterator,
    ) {}

    public function arrayIterator(): \ArrayIterator
    {
        return $this->arrayIterator;
    }
}
