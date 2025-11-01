<?php

declare(strict_types=1);

use Kaspi\DiContainer\Exception\NotFoundException as NF;
use Kaspi\DiContainer\Finder\FinderClosureCode as FC;
use Psr\Container\ContainerInterface;
use function Kaspi\DiContainer\diCallable;
use const Tests\Fixtures\LALA_LAND;

return static function (): Generator {
    yield 'fn' => diCallable(
        definition: static fn (ContainerInterface $container): mixed => $container->has(FC::class)
            ? $container->get(FC::class)
            : throw new NF(\sprintf('Const is "%s" in directory "%s"', LALA_LAND, __DIR__)),
        isSingleton: true,
    );
};
