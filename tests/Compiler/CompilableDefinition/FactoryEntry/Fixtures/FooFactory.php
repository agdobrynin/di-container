<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\FactoryEntry\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use LogicException;
use Psr\Container\ContainerInterface;

use function getenv;
use function is_string;

final class FooFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): Foo
    {
        if (is_string($token = getenv('APP_TOKEN'))) {
            return new Foo($token);
        }

        throw new LogicException('Token must be a string');
    }
}
