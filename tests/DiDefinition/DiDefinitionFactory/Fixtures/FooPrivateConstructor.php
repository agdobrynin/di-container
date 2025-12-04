<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionFactory\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

final class FooPrivateConstructor implements DiFactoryInterface
{
    private function __construct() {}

    public function __invoke(ContainerInterface $container): string
    {
        return 'ok';
    }
}
