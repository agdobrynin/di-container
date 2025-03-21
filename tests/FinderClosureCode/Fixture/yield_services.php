<?php

declare(strict_types=1);

namespace Tests\FinderClosureCode\Fixture;

use Kaspi\DiContainer\Finder\FinderClosureCode;
use Psr\Container\ContainerInterface;

return static function (): \Generator {

    yield 'fn' => fn (ContainerInterface $container) => $container->get(FinderClosureCode::class);
};
