<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\DiFactory\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;
use stdClass;

final class FactoryMainFail implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): stdClass
    {
        return new stdClass();
    }
}
