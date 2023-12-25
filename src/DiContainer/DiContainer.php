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
 * @internal
 *
 * @template TClass of object
 */
class DiContainer implements DiContainerInterface
{
    protected array $config = [];
    protected array $resolved = [];

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(
        array $config = [],
        protected ?AutowiredInterface $autowire = null,
    ) {
        foreach ($config as $id => $abstract) {
            $key = \is_string($id) ? $id : $abstract;
            $this->set($key, $abstract);
        }
    }

    /**
     * @param class-string<TClass> $id
     *
     * @return mixed|TClass
     */
    public function get(string $id): mixed
    {
        return $this->resolved[$id] ?? $this->resolve($id);
    }

    public function has(string $id): bool
    {
        return isset($this->config[$id])
            || \class_exists($id)
            || \interface_exists($id);
    }

    public function set(string $id, mixed $abstract = null): self
    {
        if (null === $abstract) {
            $abstract = $id;
        }

        if (isset($this->config[$id])) {
            throw new ContainerException("Key [{$id}] already registered in container.");
        }

        if (\is_array($abstract)
            && $constructParams = $this->parseConstructorArguments($id, $abstract)) {
            $this->config = \array_merge($this->config, $constructParams);
        } else {
            $this->config[$id] = $abstract;
        }

        return $this;
    }

    protected function parseConstructorArguments(string $id, array $params): ?array
    {
        if ([] !== $params && \class_exists($id) && $this->autowire) {
            $newParams = [];

            foreach ($params as $argName => $argValue) {
                $key = $this->autowire
                    ->getKeyGeneratorForNamedParameter()
                    ->idConstructor($id, $argName)
                ;
                $newParams[$key] = $argValue;
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

        /** @var null|class-string<TClass> $abstract */
        $abstract = match (true) {
            \class_exists($id) => $id,
            \interface_exists($id) => $this->config[$id] ?? $id,
            isset($this->config[$id]) && \is_callable($this->config[$id]) => $this->config[$id],
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

        $this->resolved[$id] = $this->config[$id];

        return $this->resolved[$id];
    }
}
