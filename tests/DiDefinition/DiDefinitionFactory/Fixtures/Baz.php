<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionFactory\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

final class Baz implements DiFactoryInterface
{
    private ?Bar $bar = null;

    public function __invoke(ContainerInterface $container): mixed
    {
        return 'ok '.(null === $this->bar ? 'nothing' : $this->bar::class);
    }

    public function withBar(Bar $bar): self
    {
        $new = clone $this;
        $new->bar = $bar;

        return $new;
    }

    public function setBar(Bar $bar): void
    {
        $this->bar = $bar;
    }
}
