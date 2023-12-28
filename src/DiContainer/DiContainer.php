<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\NotFoundContainerException;
use Kaspi\DiContainer\Interfaces\AutowiredInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\Interfaces\KeyGeneratorForNamedParameterInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @internal
 *
 * @template TClass of object
 */
class DiContainer implements DiContainerInterface
{
    public const ARGUMENTS = 'arguments';

    protected iterable $definitions = [];
    protected array $resolved = [];
    protected ?KeyGeneratorForNamedParameterInterface $keyGenerator;

    /**
     * @param iterable<string, mixed> $definitions
     *
     * @throws ContainerExceptionInterface
     */
    public function __construct(
        iterable $definitions = [],
        protected ?AutowiredInterface $autowire = null,
    ) {
        $this->keyGenerator = $this->autowire?->getKeyGeneratorForNamedParameter();

        foreach ($definitions as $id => $abstract) {
            $key = \is_string($id) ? $id : $abstract;
            $this->set($key, $abstract);
        }
    }

    /**
     * @param class-string<TClass>|string $id
     *
     * @return mixed|TClass
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

    public function set(string $id, mixed $abstract = null, ?array $arguments = null): self
    {
        if (null === $abstract) {
            $abstract = $id;
        }

        if (isset($this->definitions[$id])) {
            throw new ContainerException("Key [{$id}] already registered in container.");
        }

        $args = match (true) {
            null !== $arguments => $arguments,
            \is_iterable($abstract) => $abstract[self::ARGUMENTS] ?? null,
            default => null,
        };

        if (\is_iterable($args)
            && $constructParams = $this->parseConstructorArguments($id, (array) $args)
        ) {
            $this->definitions = \array_merge($this->definitions, $constructParams);
        } else {
            $this->definitions[$id] = $abstract;
        }

        return $this;
    }

    protected function parseConstructorArguments(string $id, array $params): ?array
    {
        if ([] !== $params && \class_exists($id) && $this->keyGenerator) {
            $newParams = [];

            foreach ($params as $argName => $argValue) {
                $key = $this->keyGenerator->idConstructor($id, $argName);

                if ($this->keyGenerator
                    && \is_string($argValue)
                    && \str_starts_with($argValue, $this->keyGenerator->delimiter())
                ) {
                    $offset = strlen($this->keyGenerator->delimiter());
                    $newParams[$key] = $this->get(substr($argValue, $offset));
                } else {
                    $newParams[$key] = $argValue;
                }
            }

            return $newParams;
        }

        return null;
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
        $abstract = match (true) {
            \class_exists($id) => $id,
            \interface_exists($id) => $definition,
            \is_callable($definition) => $definition,
            default => null
        };

        if ($abstract) {
            try {
                if (null === $this->autowire) {
                    throw new AutowiredException("Unable instantiate id [{$id}] by autowire.");
                }

                $this->resolved[$id] = $this->autowire->resolveInstance($this, $abstract);

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
}
