<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

interface DefinitionsLoaderInterface
{
    /**
     * @param non-empty-string ...$file
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     * @throws DiDefinitionExceptionInterface
     * @throws ContainerAlreadyRegisteredExceptionInterface
     */
    public function load(bool $overrideDefinitions, string ...$file): static;

    /**
     * @return iterable<class-string|non-empty-string, DiDefinitionInterface|mixed>
     */
    public function definitions(): iterable;
}
