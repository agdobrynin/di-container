<?php

declare(strict_types=1);

namespace Tests\Unit\Definition\Fixtures;

use Psr\Container\ContainerInterface;

class CallableStaticMethodWithArgument
{
    public static function makeSomething(ContainerInterface $container, WithoutConstructor $someClass, string $var = 'ok'): string
    {
        return $container->get('service')::class.':'.$someClass::class.':'.$var;
    }
}
