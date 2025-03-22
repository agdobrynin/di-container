<?php

declare(strict_types=1);

namespace Tests\FinderClosureCode\Fixture;

use Kaspi\DiContainer\Finder\{FinderClosureCode as FC, FinderFile};
use Psr\Container\ContainerInterface;

return static function (): \Generator {

    yield 'fn' => fn (ContainerInterface $container) => $container->get(FC::class);

    yield 'fn2' => function () {
        return new FinderFile();
    };
};
