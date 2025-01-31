<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable\Fixtures;

use Kaspi\DiContainer\Attributes\TaggedAs;

class ClassWithTaggedArg
{
    public function __construct(
        #[TaggedAs('tags.callable-handlers')]
        public iterable $tagged
    ) {}
}
