<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class LazyDefinitionIterator implements \Iterator, ContainerInterface
{
    /**
     * @param array<non-empty-string, non-empty-string> $mapKeyToContainerIdentifier key to container identifier
     */
    public function __construct(
        private ContainerInterface $container,
        private array $mapKeyToContainerIdentifier,
    ) {}

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function current(): mixed
    {
        return $this->valid()
            ? $this->container->get(\current($this->mapKeyToContainerIdentifier))
            : null; // @todo may be throw an exception?
    }

    public function next(): void
    {
        \next($this->mapKeyToContainerIdentifier);
    }

    /**
     * @return null|non-empty-string
     */
    public function key(): ?string
    {
        return \key($this->mapKeyToContainerIdentifier);
    }

    public function valid(): bool
    {
        return null !== \key($this->mapKeyToContainerIdentifier);
    }

    public function rewind(): void
    {
        \reset($this->mapKeyToContainerIdentifier);
    }

    /**
     * @param non-empty-string $id
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function get(string $id): mixed
    {
        if ($this->has($id)) {
            return $this->container->get($this->mapKeyToContainerIdentifier[$id]);
        }

        throw new NotFoundException(\sprintf('Definition "%s" not found.', $id));
    }

    public function has(string $id): bool
    {
        return isset($this->mapKeyToContainerIdentifier[$id]);
    }
}
