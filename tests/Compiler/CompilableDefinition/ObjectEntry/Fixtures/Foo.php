<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures;

use Psr\Container\ContainerInterface;

final class Foo
{
    private Baz $baz;

    public function __construct(private Bar $bar, private ?ContainerInterface $container = null) {}

    public function setBaz(Baz $baz): void
    {
        $this->baz = $baz;
    }

    public function withContainer(ContainerInterface $container): self
    {
        $new = clone $this;
        $new->container = $container;

        return $new;
    }
}
