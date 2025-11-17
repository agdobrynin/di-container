<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionFactory\Fixtures;

use Psr\Container\ContainerInterface;

final class Bar
{
    public function __invoke(ContainerInterface $container): mixed
    {
        return 'ok';
    }
}
