<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes\Fixtures;

use Kaspi\DiContainer\Attributes\TaggedAs;

class GroupOne
{
    public function __construct(
        #[TaggedAs(name: 'tags.services.group-one', isLazy: false)]
        public array $services
    ) {}
}
