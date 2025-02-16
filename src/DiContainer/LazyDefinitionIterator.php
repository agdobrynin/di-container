<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class LazyDefinitionIterator implements \Iterator, ContainerInterface, \ArrayAccess
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
        return false !== ($id = \current($this->mapKeyToContainerIdentifier))
            ? $this->container->get($id)
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

    public function offsetExists(mixed $offset): bool
    {
        return \is_string($offset) && $this->has($offset);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function offsetGet(mixed $offset): mixed
    {
        return \is_string($offset)
            ? $this->get($offset)
            : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new ContainerException('LazyDefinitionIterator is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new ContainerException('LazyDefinitionIterator is immutable.');
    }
}
