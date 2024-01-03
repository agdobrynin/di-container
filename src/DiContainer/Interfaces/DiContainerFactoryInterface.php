<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

interface DiContainerFactoryInterface
{
    /**
     * @param iterable<string, mixed> $definitions
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public static function make(
        iterable $definitions = [],
        string $linkContainerSymbol = '@',
        $delimiterLevelSymbol = '.'
    ): DiContainerInterface;
}
