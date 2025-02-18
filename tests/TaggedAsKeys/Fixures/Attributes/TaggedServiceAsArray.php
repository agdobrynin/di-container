<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys\Fixures\Attributes;

use Kaspi\DiContainer\Attributes\TaggedAs;

final class TaggedServiceAsArray
{
    public function __construct(
        #[TaggedAs('tags.one', isLazy: false, key: 'key', keyDefaultMethod: 'getKey')]
        public array $items
    ) {}
}
