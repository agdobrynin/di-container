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
    protected int $linkContainerSymbolLength;
    protected string $accessArrayNotationRegularExpression;

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

        $this->linkContainerSymbolLength = \strlen($linkContainerSymbol);

        $this->accessArrayNotationRegularExpression = '/^'.\preg_quote($this->linkContainerSymbol, '/').
            '((?:\w+'.\preg_quote($this->delimiterAccessArrayNotationSymbol, '/').')+)\w+$/u';

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

        /** @var null|class-string<TClass>|\Closure $definition */
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

                $constructorArgs = [];

                if (\is_string($definition)) {
                    if ($definitionArguments instanceof \Closure) {
                        return $this->resolved[$id] = $this->autowire->resolveInstance($this, $definitionArguments);
                    }

                    if (\is_string($definitionArguments)
                        && $definition !== $definitionArguments
                        && \class_exists($definitionArguments)) {
                        return \is_a($definitionArguments, DiFactoryInterface::class, true)
                            ? $this->resolved[$id] = $this->get($definitionArguments)($this)
                            : throw new ContainerException("Definition argument '{$definitionArguments}' must be implement '".DiFactoryInterface::class."' interface");
                    }

                    $paramsDefinitions = $this->argumentDefinitions[$definition]
                        ?? $definitionArguments[DiContainerInterface::ARGUMENTS]
                        ?? [];

                    foreach ($paramsDefinitions as $argName => $argValue) {
                        $constructorArgs[$argName] = $this->getValue($argValue);
                    }
                }

                return $this->resolved[$id] = $this->autowire->resolveInstance($this, $definition, $constructorArgs);
            } catch (AutowiredExceptionInterface $exception) {
                throw new ContainerException(
                    message: $exception->getMessage(),
                    code: $exception->getCode(),
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
        $isStringValue = \is_string($value);
        $isArrayNotationValue = $isStringValue
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
        return (\str_starts_with($value, $this->linkContainerSymbol))
            ? \substr($value, $this->linkContainerSymbolLength)
            : null;
    }

    protected function hasArrayNotation(string $id): bool
    {
        try {
            return \preg_match($this->accessArrayNotationRegularExpression, $id)
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
}
