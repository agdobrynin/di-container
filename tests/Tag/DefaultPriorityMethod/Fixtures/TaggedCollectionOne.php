<?php

declare(strict_types=1);

namespace Tests\Tag\DefaultPriorityMethod\Fixtures;

use Kaspi\DiContainer\Attributes\TaggedAs;

final class TaggedCollectionOne
{
    public function __construct(
        #[TaggedAs(TaggedInterface::class, defaultPriorityMethod: 'getCollectionPriority')]
        public iterable $items
    ) {}
}
