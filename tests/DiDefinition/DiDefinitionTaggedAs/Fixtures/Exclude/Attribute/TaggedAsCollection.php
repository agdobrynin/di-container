<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Exclude\Attribute;

use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Attributes\TaggedAs;

#[Tag('tags.aaa')]
final class TaggedAsCollection
{
    public function __construct(
        #[TaggedAs('tags.aaa', containerIdExclude: [Three::class])]
        public iterable $items
    ) {}
}
