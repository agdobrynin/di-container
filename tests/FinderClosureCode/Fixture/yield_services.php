<?php

declare(strict_types=1);

namespace Tests\FinderClosureCode\Fixture;

use CONST Tests\Fixtures\LALA_LAND;
// ok
use Kaspi\DiContainer\{
    Exception\NotFoundException as NF,
};
use Kaspi\DiContainer\Finder\{
    FinderClosureCode as FC,
    FinderFile,
};
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface, Psr\Container\ContainerExceptionInterface;
use function array_map;
use const T_CLASS;
use const T_ABSTRACT, T_INTERFACE;

return static function (): \Generator {

    yield 'fn' => fn(ContainerInterface $container) => $container->has(FC::class)
        ? $container->get(FC::class)
        : throw new NF(\sprintf('Uups. %s', LALA_LAND));

    yield 'fn2' => function (array $params, int $tokenId) {
        $res = array_map('implode', $params);
        $label = match ($tokenId) {
            T_CLASS => 'php class',
            T_ABSTRACT => 'is abstract',
            T_INTERFACE => 'is php interface',
        };

        return new FinderFile();
    };
};
