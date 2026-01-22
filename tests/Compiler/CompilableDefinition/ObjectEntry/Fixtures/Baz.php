<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures;

use Psr\Container\ContainerInterface;

final class Baz
{
    private ?ContainerInterface $container = null;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }
}
