<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Closure;
use Iterator;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\IdsIteratorInterface;
use Kaspi\DiContainer\Interfaces\DiContainerGetterDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Psr\Container\ContainerExceptionInterface;

use function sprintf;

final class DiContainerDefinitions implements DiContainerDefinitionsInterface
{
    /**
     * Excluded container identifiers from definition iterator.
     *
     * @var array<non-empty-string, true>
     */
    private array $excludeContainerIdentifier = [];

    public function __construct(private readonly DiContainerGetterDefinitionInterface&DiContainerInterface $container, private readonly IdsIteratorInterface $idsIterator) {}

    public function getContainer(): DiContainerInterface
    {
        return $this->container;
    }

    public function isSingletonDefinitionDefault(): bool
    {
        return $this->container->getConfig()->isSingletonServiceDefault();
    }

    public function getDefinitions(?Closure $fallback = null): Iterator
    {
        $sentContainerIdentifiers = [];

        foreach ($this->container->getDefinitions() as $id => $definition) {
            if (!isset($this->excludeContainerIdentifier[$id])) {
                $sentContainerIdentifiers[$id] = true;

                yield $id => $definition;
            }
        }

        while (false !== ($id = $this->idsIterator->current())) {
            if (!isset($sentContainerIdentifiers[$id]) && !isset($this->excludeContainerIdentifier[$id])) {
                yield $id => $this->getDefinition($id, $fallback);
            }

            $this->idsIterator->next();
        }
    }

    public function getDefinition(string $containerIdentifier, ?Closure $fallback = null): mixed
    {
        try {
            return $this->container->getDefinition($containerIdentifier);
        } catch (ContainerExceptionInterface $e) {
            if (null === $fallback) {
                throw new DefinitionCompileException(
                    sprintf('Cannot get definition via container identifier "%s".', $containerIdentifier),
                    previous: $e
                );
            }

            return ($fallback)($containerIdentifier, $e);
        }
    }

    public function pushToDefinitionIterator(string $containerIdentifier): void
    {
        $this->idsIterator->add($containerIdentifier);
    }

    public function excludeContainerIdentifier(string ...$containerIdentifier): void
    {
        foreach ($containerIdentifier as $id) {
            if (!isset($this->excludeContainerIdentifier[$id])) {
                $this->excludeContainerIdentifier[$id] = true;
            }
        }
    }

    public function reset(): void
    {
        $this->excludeContainerIdentifier = [];
        $this->idsIterator->reset();
    }
}
