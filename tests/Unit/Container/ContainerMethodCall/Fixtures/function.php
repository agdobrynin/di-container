<?php

declare(strict_types=1);

namespace Tests\Unit\Container\ContainerMethodCall\Fixtures;

use Psr\Container\ContainerInterface;

function testFunction(ContainerInterface $container, string $icon): string
{
    return $container->get('hello').$icon;
}

function testFunctionNotTypedParameter($container, $var)
{
    return $container->get($var).'!!!';
}
