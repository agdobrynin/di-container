<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

use Closure;
use Iterator;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\ResetInterface;
use Throwable;

interface DiContainerDefinitionsInterface extends ResetInterface
{
    public function getContainer(): DiContainerInterface;

    public function isSingletonDefinitionDefault(): bool;

    /**
     * Get definitions from `\Kaspi\DiContainer\Interfaces\DiContainerInterface::getDefinitions()`.
     *
     * @param null|Closure(string $containerIdentifier, Throwable $e): mixed $fallback
     *
     * @return Iterator<non-empty-string, mixed>
     *
     * @throws DefinitionCompileExceptionInterface
     */
    public function getDefinitions(?Closure $fallback = null): Iterator;

    /**
     * Get a definition from container.
     *
     * @param non-empty-string                                               $containerIdentifier
     * @param null|Closure(string $containerIdentifier, Throwable $e): mixed $fallback
     *
     * @throws DefinitionCompileExceptionInterface
     */
    public function getDefinition(string $containerIdentifier, ?Closure $fallback = null): mixed;

    /**
     * Add definition via container identifier into definition iterator even if definition is not configured in container.
     * This definition will be received later in method `static::getDefinitions()`.
     *
     * @param non-empty-string $containerIdentifier
     */
    public function pushToDefinitionIterator(string $containerIdentifier): void;

    /**
     * Exclude container identifier from definitions iterator.
     * Definition with container identifier will be excluded in method `static::getDefinitions()`.
     *
     * @param non-empty-string ...$containerIdentifier
     */
    public function excludeContainerIdentifier(string ...$containerIdentifier): void;
}
