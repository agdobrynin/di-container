<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\ObjectEntry\FixturesForCompile;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(isSingleton: true, resetter: 'flush')]
final class Foo
{
    private string $foo = 'Lorem ipsum foo';

    public function getFoo(): string
    {
        return $this->foo;
    }

    public function flush(): void
    {
        $this->foo = 'reset foo';
    }

    public function doResetManually(): void
    {
        $this->foo = 'manually foo';
    }
}
