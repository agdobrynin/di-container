<?php

declare(strict_types=1);

namespace Tests\Integration\Fixtures\ResolveExcludedIds;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

final class DiFactoryPerson implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): Person
    {
        return new Person('Ivan', 'Petrov', 22);
    }
}
