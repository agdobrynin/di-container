<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

use Iterator;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;

interface DiContainerDefinitionsInterface
{
    public function getContainer(): DiContainerInterface;

    public function isSingletonDefinitionDefault(): bool;

    /**
     * Get definitions from `\Kaspi\DiContainer\Interfaces\DiContainerInterface::getDefinitions()`.
     *
     * @return Iterator<non-empty-string, mixed>
     */
    public function getDefinitions(): Iterator;

    /**
     * Add definition via container identifier into definition iterator even if definition is not configured in container.
     * This definition will be received later in method `static::getDefinitions()`.
     *
     * @param non-empty-string $containerIdentifier
     */
    public function pushToDefinitionIterator(string $containerIdentifier): void;
}
