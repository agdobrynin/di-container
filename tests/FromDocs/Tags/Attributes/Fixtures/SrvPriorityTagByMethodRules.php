<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes\Fixtures;

use Kaspi\DiContainer\Attributes\TaggedAs;

class SrvPriorityTagByMethodRules
{
    public function __construct(
        #[TaggedAs('tags.rules.priorityMethod', isLazy: false, defaultPriorityMethod: 'getCollectionPriority')]
        public iterable $rules
    ) {}
}
