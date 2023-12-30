<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\NotFoundContainerException;
use Kaspi\DiContainer\Interfaces\AutowiredInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @template TClass of object
 */
class DiContainer implements DiContainerInterface
{
    public const ARGUMENTS = 'arguments';

    protected iterable $definitions = [];

    /**
     * Arguments for constructor of class.
     *
     * @var array|iterable<class-string, array>
     */
    protected iterable $argumentDefinitions = [];
    protected array $resolved = [];

    /**
     * @param iterable<string, mixed> $definitions
     *
     * @throws ContainerExceptionInterface
     */
    public function __construct(
        iterable $definitions = [],
        protected ?AutowiredInterface $autowire = null,
        protected string $linkContainerSymbol = '@',
    ) {
        foreach ($definitions as $id => $abstract) {
            $key = \is_string($id) ? $id : $abstract;
            $this->set($key, $abstract);
        }
    }

    /**
     * @param class-string<TClass>|string $id
     *
     * @return TClass
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function get(string $id): mixed
    {
        return $this->resolved[$id] ?? $this->resolve($id);
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id])
            || \class_exists($id)
            || \interface_exists($id);
    }

    public function set(string $id, mixed $abstract = null, ?array $arguments = null): static
    {
        if (isset($this->definitions[$id])) {
            throw new ContainerException("Key [{$id}] already registered in container.");
        }

        if (null === $abstract) {
            $abstract = $id;
        }

        if ($arguments) {
            $this->argumentDefinitions[$id] = $arguments;
        }

        $this->definitions[$id] = $abstract;

        return $this;
    }

    /**
     * Resolve dependencies.
     *
     * @param class-string<TClass> $id
     *
     * @return mixed|TClass
     *
     * @throws NotFoundExceptionInterface  no entry was found for **this** identifier
     * @throws ContainerExceptionInterface error while retrieving the entry
     */
    protected function resolve(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new NotFoundContainerException("Unresolvable dependency [{$id}].");
        }

        $definition = $this->definitions[$id] ?? $id;

        /** @var null|class-string<TClass> $abstract */
        [$abstract, $constructorArguments] = match (true) {
            \class_exists($id) => [
                $id,
                $definition,
            ],
            \is_callable($definition) => [
                $definition,
                null,
            ],
            \interface_exists($id) => [
                $definition,
                \is_callable($definition) ? [] : ($this->definitions[$definition] ?? null),
            ],
            default => [null, null],
        };

        if ($abstract) {
            try {
                if (null === $this->autowire) {
                    throw new AutowiredException("Unable instantiate id [{$id}] by autowire.");
                }

                $args = [];

                if (\is_string($abstract)) {
                    $paramsDefinitions = $this->argumentDefinitions[$abstract]
                        ?? $constructorArguments[self::ARGUMENTS]
                        ?? [];
                    $args = $this->parseConstructorArguments($abstract, $paramsDefinitions);
                }

                $this->resolved[$id] = $this->autowire->resolveInstance($this, $abstract, $args);

                return $this->resolved[$id];
            } catch (AutowiredExceptionInterface $exception) {
                throw new ContainerException(
                    message: $exception->getMessage(),
                    previous: $exception->getPrevious()
                );
            }
        }

        $this->resolved[$id] = $this->definitions[$id];

        return $this->resolved[$id];
    }

    protected function parseConstructorArguments(string $id, array $params): array
    {
        if (!\class_exists($id)) {
            throw new ContainerException("Class [{$id}] not exist.");
        }

        $newParams = [];

        foreach ($params as $argName => $argValue) {
            if (\is_string($argValue)
                && \str_starts_with($argValue, $this->linkContainerSymbol)) {
                $newParams[$argName] = $this->get(
                    substr(
                        $argValue,
                        strlen($this->linkContainerSymbol)
                    )
                );
            } else {
                $newParams[$argName] = $argValue;
            }
        }

        return $newParams;
    }
}
