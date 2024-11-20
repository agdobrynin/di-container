<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\InjectContext;

class InjectSimpleArgument
{
    public function __construct(
        #[InjectContext(arguments: ['array' => ['first' => '🥇', 'second' => '🥈']])]
        protected \ArrayIterator $arrayIterator,
    ) {}

    public function arrayIterator(): \ArrayIterator
    {
        return $this->arrayIterator;
    }
}
