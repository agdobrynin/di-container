<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\ConflictConfigureViaDiFactory;

use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(FooFactory::class)]
final class Foo
{
    public function __construct(public readonly string $str) {}
}
