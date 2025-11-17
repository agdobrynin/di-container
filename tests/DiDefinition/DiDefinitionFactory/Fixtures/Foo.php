<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionFactory\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

final class Foo implements DiFactoryInterface
{
    public function __construct(public readonly string $fooStr, public readonly Bar $bar) {}

    public function __invoke(ContainerInterface $container): mixed
    {
        return 'ok '.$this->fooStr.' '.$this->bar::class;
    }
}
