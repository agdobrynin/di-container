<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionFactory\Fixtures;

use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

final class Bat implements DiFactoryInterface
{
    private ?Bar $bar = null;

    public function __invoke(ContainerInterface $container): mixed
    {
        return 'ok '.(null === $this->bar ? 'nothing' : $this->bar::class);
    }

    #[Setup(new DiGet(Bar::class))]
    public function setBar(Bar $bar): void
    {
        $this->bar = $bar;
    }
}
