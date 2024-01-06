<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\NotFoundException;
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
        protected string $delimiterAccessArrayNotationSymbol = '.',
    ) {
        if ($linkContainerSymbol === $delimiterAccessArrayNotationSymbol) {
            throw new ContainerException(
                "Delimiters symbols must be different. Got link container symbol [{$linkContainerSymbol}], delimiter level symbol [{$delimiterAccessArrayNotationSymbol}]"
            );
        }

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
        if (isset($this->resolved[$id])) {
            return $this->resolved[$id];
        }

        $this->resolveArrayNotation($id);

        return $this->resolve($id);
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id])
            || \class_exists($id)
            || \interface_exists($id)
            || $this->hasArrayNotation($id);
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
            throw new NotFoundException("Unresolvable dependency [{$id}].");
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
                        $args[$argName] = $this->getValue($argValue);
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

        $this->resolved[$id] = isset($this->definitions[$id]) && $this->definitions[$id] === $id
        ? $id
        : $this->getValue($this->definitions[$id]);

        return $this->resolved[$id];
    }

    protected function getValue(mixed $value): mixed
    {
        if (\is_string($value) && $key = $this->parseLinkSymbol($value)) {
            return $this->getValue($this->resolve($key));
        }

        return \is_string($value) && ($this->has($value) || $this->isAccessArrayNotation($value))
            ? $this->resolve($value)
            : $value;
    }

    protected function parseLinkSymbol(mixed $value): ?string
    {
        return (\is_string($value) && \str_starts_with($value, $this->linkContainerSymbol))
            ? \substr($value, \strlen($this->linkContainerSymbol))
            : null;
    }

    protected function isAccessArrayNotation(string $id): bool
    {
        $delimiter = \preg_quote($this->delimiterAccessArrayNotationSymbol, null);

        return (bool) \preg_match('/^((?:\w+'.$delimiter.')+)\w+$/u', $id);
    }

    protected function hasArrayNotation(string $id): bool
    {
        try {
            return $this->resolveArrayNotation($id);
        } catch (NotFoundException) {
            return false;
        }
    }

    protected function resolveArrayNotation(string $path): bool
    {
        if ($this->isAccessArrayNotation($path)) {
            if (!isset($this->definitions[$path])) {
                $this->definitions[$path] = \array_reduce(
                    \explode($this->delimiterAccessArrayNotationSymbol, $path),
                    static function (mixed $segments, string $segment) use ($path) {
                        return isset($segments[$segment]) && \is_array($segments)
                            ? $segments[$segment]
                            : throw new NotFoundException("Unresolvable dependency: array notation key [{$path}]");
                    },
                    $this->definitions
                );
            }

            return true;
        }

        return false;
    }
}
