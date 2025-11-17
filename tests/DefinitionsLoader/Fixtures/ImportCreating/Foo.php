<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\ImportCreating;

use Kaspi\DiContainer\Attributes\DiFactory;
use Tests\DefinitionsLoader\Fixtures\ImportCreating\Factory\FactoryFoo;

#[DiFactory(FactoryFoo::class, true)]
final class Foo
{
    public function __construct(public readonly ?string $secure = null) {}
}
