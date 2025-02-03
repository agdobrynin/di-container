<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes\Fixtures;

use Kaspi\DiContainer\Attributes\TaggedAs;

class SrvPriorityTagRules
{
    public function __construct(
        #[TaggedAs('tags.rules')]
        public iterable $rules
    ) {}
}
