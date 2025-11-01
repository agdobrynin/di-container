<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @phpstan-type NotParsedCallable array{0?: object|non-empty-string, 1?:non-empty-string}|non-empty-string
 * @phpstan-type ParsedCallable array{0: object|non-empty-string, 1?:non-empty-string}|callable|\Closure|callable-string
 */
interface DiContainerCallInterface
{
    /**
     * @param array<class-string, null|non-empty-string>|callable|class-string|non-empty-string $definition
     * @param array<non-empty-string|non-negative-int, mixed>                                   $arguments
     *
     * @phpstan-param NotParsedCallable|ParsedCallable $definition
     *
     * @throws ContainerExceptionInterface
     * @throws CallCircularDependencyException
     * @throws NotFoundExceptionInterface
     */
    public function call(array|callable|string $definition, array $arguments = []): mixed;
}
