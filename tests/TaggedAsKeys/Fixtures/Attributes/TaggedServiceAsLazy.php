<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\TaggedAs;

final class TaggedServiceAsLazy
{
    public function __construct(
        #[TaggedAs('tags.one', key: 'key', keyDefaultMethod: 'getKey')]
        public iterable $items
    ) {}

    public static function getKeyByMethod(
        #[TaggedAs('tags.other', key: 'key.method')]
        iterable $items
    ): iterable {
        return $items;
    }
}
