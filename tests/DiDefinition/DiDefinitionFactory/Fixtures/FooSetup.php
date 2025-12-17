<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionFactory\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

final class FooSetup implements DiFactoryInterface
{
    public function __construct() {}

    public function __destruct() {}

    public function __invoke(ContainerInterface $container): mixed
    {
        // TODO: Implement __invoke() method.
    }
}
