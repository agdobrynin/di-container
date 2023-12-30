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
        return $this->resolved[$this->parseLinkSymbol($id) ?: $id]
            ?? $this->resolve($id);
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id])
            || \class_exists($id)
            || \interface_exists($id)
            || isset($this->definitions[$this->parseLinkSymbol($id)]);
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

        /** @var null|class-string<TClass> $definition */
        [$definition, $definitionArguments] = match (true) {
            \class_exists($id) => [
                $id,
                $this->definitions[$id] ?? null,
            ],
            \interface_exists($id) => [
                $this->definitions[$id]
                    ?? throw new ContainerException("No class defined for interface [{$id}]"),
                \is_string($this->definitions[$id])
                    ? $this->definitions[$this->definitions[$id]] ?? null
                    : null,
            ],
            ($this->definitions[$id] ?? null) instanceof \Closure => [
                $this->definitions[$id],
                null,
            ],
            default => [null, null],
        };

        if ($definition) {
            try {
                if (null === $this->autowire) {
                    throw new AutowiredException("Unable instantiate id [{$id}] by autowire.");
                }

                $args = [];

                if (\is_string($definition)) {
                    $paramsDefinitions = $this->argumentDefinitions[$definition]
                        ?? $definitionArguments[self::ARGUMENTS]
                        ?? [];

                    foreach ($paramsDefinitions as $argName => $argValue) {
                        $args[$argName] = $this->getValueOrLinkSymbol($argValue);
                    }
                }

                $this->resolved[$id] = $this->autowire->resolveInstance($this, $definition, $args);

                return $this->resolved[$id];
            } catch (AutowiredExceptionInterface $exception) {
                throw new ContainerException(
                    message: $exception->getMessage(),
                    previous: $exception->getPrevious()
                );
            }
        }

        $this->resolved[$id] = $this->definitions[$this->parseLinkSymbol($id) ?: $id];

        return $this->resolved[$id];
    }

    protected function getValueOrLinkSymbol(mixed $value)
    {
        if ($key = $this->parseLinkSymbol($value)) {
            return $this->getValueOrLinkSymbol($this->get($key));
        }

        return $value;
    }

    protected function parseLinkSymbol(mixed $value): ?string
    {
        return (\is_string($value)
            && \str_starts_with($value, $this->linkContainerSymbol))
            ? substr($value, strlen($this->linkContainerSymbol))
            : null;
    }
}
