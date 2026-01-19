<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @phpstan-type NotParsedCallable array{0: object|non-empty-string, 1:non-empty-string}|class-string|callable|callable-string|non-empty-string
 * @phpstan-type ParsedCallable array{0: object|non-empty-string, 1:non-empty-string}|callable|callable-string
 */
interface DiContainerCallInterface
{
    /**
     * @param NotParsedCallable|ParsedCallable $definition
     * @param DiDefinitionInterface|mixed      ...$argument
     *
     * @throws ContainerExceptionInterface
     * @throws DiDefinitionExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function call(array|callable|string $definition, mixed ...$argument): mixed;
}
