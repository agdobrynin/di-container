<?php

declare(strict_types=1);

namespace Tests\AttributeReader\DiFactory\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

final class IntFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): int
    {
        return 100_001;
    }
}
