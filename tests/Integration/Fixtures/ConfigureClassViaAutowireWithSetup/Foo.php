<?php

declare(strict_types=1);

namespace Tests\Integration\Fixtures\ConfigureClassViaAutowireWithSetup;

use Kaspi\DiContainer\Attributes\TaggedAs;

final class Foo
{
    public function __construct(
        #[TaggedAs('tags.one')]
        public readonly iterable $taggedOne,
        #[TaggedAs('tags.two')]
        public readonly iterable $taggedTwo,
    ) {}
}
