<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw\Fixtures;

use Psr\Container\ContainerInterface;

final class Foo
{
    public static function bar(ContainerInterface $container): mixed
    {
        return $container->get('services.foo_bar');
    }
}
