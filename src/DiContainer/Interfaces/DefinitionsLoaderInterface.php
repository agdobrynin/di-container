<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

interface DefinitionsLoaderInterface
{
    /**
     * @param non-empty-string ...$file
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     * @throws ContainerExceptionInterface
     */
    public function load(bool $overrideDefinitions, string ...$file): static;

    /**
     * @param iterable<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed> $definitions
     *
     * @return $this
     *
     * @throws DiDefinitionExceptionInterface
     * @throws ContainerAlreadyRegisteredExceptionInterface
     */
    public function addDefinitions(bool $overrideDefinitions, iterable $definitions): static;

    /**
     * @return iterable<class-string|non-empty-string, DiDefinitionInterface|mixed>
     */
    public function definitions(): iterable;

    /**
     * Load classes from directories.
     *
     * @param list<non-empty-string> $src     find pathnames matching a pattern
     * @param list<non-empty-string> $exclude exclude pathnames matching a pattern
     *
     * @return $this
     */
    public function resources(array $src, array $exclude = []): static;
}
