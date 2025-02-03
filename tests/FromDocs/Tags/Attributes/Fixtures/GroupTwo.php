<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes\Fixtures;

use Kaspi\DiContainer\Attributes\TaggedAs;

class GroupTwo
{
    public function __construct(
        #[TaggedAs('tags.services.group-two')]
        public iterable $services
    ) {}
}
