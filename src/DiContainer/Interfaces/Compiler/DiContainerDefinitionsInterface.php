<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

use Closure;
use Iterator;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\ResetInterface;
use Psr\Container\NotFoundExceptionInterface;

interface DiContainerDefinitionsInterface extends ResetInterface
{
    public function getContainer(): DiContainerInterface;

    public function isSingletonDefinitionDefault(): bool;

    /**
     * Get definitions from `\Kaspi\DiContainer\Interfaces\DiContainerInterface::getDefinitions()`.
     *
     * @param null|Closure(string $containerIdentifier, DefinitionCompileExceptionInterface|NotFoundExceptionInterface $e): mixed $fallback
     *
     * @return Iterator<non-empty-string, mixed>
     *
     * @throws DefinitionCompileExceptionInterface
     */
    public function getDefinitions(?Closure $fallback = null): Iterator;

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
