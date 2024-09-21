<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\AutowiredInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @template T of object
 */
class DiContainer implements DiContainerInterface
{
    protected iterable $definitions = [];

    /**
     * Arguments for a constructor of class.
     *
     * @var array|iterable<class-string, array>
     */
    protected iterable $argumentDefinitions = [];
    protected array $resolved = [];
    protected ?int $linkContainerSymbolLength;
    protected ?string $accessArrayNotationRegularExpression;

    /**
     * @param iterable<class-string|string, mixed|T> $definitions
     *
     * @throws ContainerExceptionInterface
     */
    public function __construct(
        iterable $definitions = [],
        protected ?AutowiredInterface $autowire = null,
        protected string $linkContainerSymbol = '@',
        protected string $delimiterAccessArrayNotationSymbol = '.'
    ) {
        if ($linkContainerSymbol === $delimiterAccessArrayNotationSymbol) {
            throw new ContainerException(
                "Delimiters symbols must be different. Got link container symbol [{$linkContainerSymbol}], delimiter level symbol [{$delimiterAccessArrayNotationSymbol}]"
            );
        }

        $this->linkContainerSymbolLength = \strlen($linkContainerSymbol) ?: null;

        $this->accessArrayNotationRegularExpression = $this->linkContainerSymbolLength && $delimiterAccessArrayNotationSymbol
            ? '/^'.\preg_quote($linkContainerSymbol, '/').'((?:\w+'.\preg_quote($delimiterAccessArrayNotationSymbol, '/').')+)\w+$/u'
            : null;

        foreach ($definitions as $id => $abstract) {
            $key = \is_string($id) ? $id : $abstract;
            $this->set($key, $abstract);
        }
    }

    /**
     * @param class-string<T>|string $id
     *
     * @return T
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
            || isset($this->resolved[$id])
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
     * @param class-string<T> $id
     *
     * @return mixed|T
     *
     * @throws NotFoundExceptionInterface  no entry was found for **this** identifier
     * @throws ContainerExceptionInterface error while retrieving the entry
     */
    protected function resolve(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new NotFoundException("Unresolvable dependency [{$id}].");
        }

        $definition = $this->definitions[$id] ?? null;

        try {
            if ($definition instanceof \Closure) {
                return $this->resolved[$id] = $this->autowire?->resolveInstance($this, $definition)
                    ?? throw new AutowiredException("Unable instantiate id [{$id}] by autowire.");
            }

            if (\is_string($definition) && \is_a($definition, DiFactoryInterface::class, true)) {
                return $this->resolved[$id] = $this->resolveInstanceByClassId($definition)($this);
            }

            if (\class_exists($id)) {
                return $this->resolved[$id] = $this->resolveInstanceByClassId($id);
            }

            if (\interface_exists($id)) {
                return $this->resolved[$id] = \is_string($definition)
                    ? $this->get($definition)
                    : throw new ContainerException("Not found definition for interface [{$id}]");
            }
        } catch (AutowiredExceptionInterface $exception) {
            throw new ContainerException(
                message: $exception->getMessage(),
                code: $exception->getCode(),
                previous: $exception->getPrevious()
            );
        }

        return $this->resolved[$id] = match (true) {
            $definition === $id => $id,
            null === $definition => null,
            default => $this->getValue($definition),
        };
    }

    protected function getValue(mixed $value): mixed
    {
        $isStringValue = \is_string($value);
        $isArrayNotationValue = $this->accessArrayNotationRegularExpression
            && $isStringValue
            && \preg_match($this->accessArrayNotationRegularExpression, $value);

        if ($isArrayNotationValue && $this->makeDefinitionForArrayNotation($value)) {
            return $this->get($value);
        }

        if ($isStringValue && $key = $this->parseLinkSymbol($value)) {
            return $this->getValue($this->get($key));
        }

        return $isStringValue && $this->has($value)
            ? $this->get($value)
            : $value;
    }

    protected function parseLinkSymbol(string $value): ?string
    {
        return $this->linkContainerSymbolLength && (\str_starts_with($value, $this->linkContainerSymbol))
            ? \substr($value, $this->linkContainerSymbolLength)
            : null;
    }

    protected function hasArrayNotation(string $id): bool
    {
        try {
            return $this->accessArrayNotationRegularExpression
                && \preg_match($this->accessArrayNotationRegularExpression, $id)
                && $this->makeDefinitionForArrayNotation($id);
        } catch (NotFoundException) {
            return false;
        }
    }

    protected function makeDefinitionForArrayNotation(string $id): bool
    {
        if (!isset($this->definitions[$id])) {
            $path = \substr($id, $this->linkContainerSymbolLength);
            $this->definitions[$id] = \array_reduce(
                \explode($this->delimiterAccessArrayNotationSymbol, $path),
                static function (mixed $segments, string $segment) use ($id) {
                    return isset($segments[$segment]) && \is_array($segments)
                        ? $segments[$segment]
                        : throw new NotFoundException("Unresolvable dependency: array notation key [{$id}]");
                },
                $this->definitions
            );
        }

        return true;
    }

    protected function resolveInstanceByClassId(string $id): mixed
    {
        if (null === $this->autowire) {
            throw new AutowiredException("Unable instantiate id [{$id}] by autowire.");
        }

        $constructorDefinedArgs = $this->argumentDefinitions[$id]
            ?? $this->definitions[$id][DiContainerInterface::ARGUMENTS]
            ?? [];

        $constructorArgs = [];

        if ([] !== $constructorDefinedArgs) {
            foreach ($constructorDefinedArgs as $argName => $argValue) {
                $constructorArgs[$argName] = $this->getValue($argValue);
            }
        }

        return $this->autowire->resolveInstance($this, $id, $constructorArgs);
    }
}
