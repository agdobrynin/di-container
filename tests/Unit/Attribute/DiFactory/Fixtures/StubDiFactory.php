<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\DiFactory\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class StubDiFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): \stdClass
    {
        return new \stdClass();
    }
}
