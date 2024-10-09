<?php

declare(strict_types=1);

namespace Tests\Unit\Callable\Fixtures;

use Psr\Container\ContainerInterface;

class ClassWithStaticMethodParams
{
    public static \ArrayIterator $arrayIterator;

    public static function addAndCopyStatic(ContainerInterface $container, string $containerId): array
    {
        self::$arrayIterator->append($container->get($containerId));
        self::$arrayIterator->rewind();

        return self::$arrayIterator->getArrayCopy();
    }
}
