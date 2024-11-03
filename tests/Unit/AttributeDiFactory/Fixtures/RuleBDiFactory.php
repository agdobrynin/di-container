<?php

declare(strict_types=1);

namespace Tests\Unit\AttributeDiFactory\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class RuleBDiFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): RuleB
    {
        return new RuleB();
    }
}
