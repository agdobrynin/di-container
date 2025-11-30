<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\InjectByCallable;

final class FooBar
{
    public function __construct(
        #[InjectByCallable([Foo::class, 'config'])]
        public readonly Foo $foo
    ) {}
}
