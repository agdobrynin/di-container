<?php

declare(strict_types=1);

namespace Tests\Tag\DefaultPriorityMethod\Fixtures;

use Kaspi\DiContainer\Attributes\TaggedAs;

final class TaggedCollectionTwo
{
    public function __construct(
        #[TaggedAs(TaggedInterface::class, priorityDefaultMethod: 'getOneOfPriority')]
        public iterable $items
    ) {}
}
