<?php

declare(strict_types=1);

namespace Tests\FinderClosureCode\Fixture;

use Kaspi\DiContainer\Finder\{FinderClosureCode as FC, FinderFile};
use Kaspi\DiContainer\Exception\NotFoundException as NF;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

return static function (): \Generator {

    yield 'fn' => fn(ContainerInterface $container) => $container->has(FC::class)
        ? $container->get(FC::class)
        : throw new NF('Uups.');

    yield 'fn2' => function () {
        return new FinderFile();
    };
};
