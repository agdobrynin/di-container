<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;
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
     * @var DiContainerDefinition[]
     */
    protected iterable $definitionCache = [];
    protected array $resolved = [];
    protected ?int $linkContainerSymbolLength = null;

    /**
     * @param iterable<class-string|string, mixed|T> $definitions
     *
     * @throws ContainerExceptionInterface
     */
    public function __construct(
        iterable $definitions = [],
        protected ?DiContainerConfigInterface $config = null
    ) {
        if ($s = $this->config?->getLinkContainerSymbol()) {
            $this->linkContainerSymbolLength = \strlen($s);
        }

        foreach ($definitions as $id => $definition) {
            $key = \is_string($id) ? $id : $definition;
            $this->set($key, $definition);
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
            || $this->hasClassOrInterface($id)
            || (
                $this->config?->isUseArrayNotationDefinition()
                && $this->hasArrayNotation($id)
            );
    }

    public function set(string $id, mixed $definition = null, ?array $arguments = null, ?bool $shared = null): static
    {
        if (isset($this->definitions[$id])) {
            throw new ContainerAlreadyRegisteredException("Key [{$id}] already registered in container.");
        }

        if (null === $definition) {
            $definition = $id;
        }

        $this->definitions[$id] = $definition;

        if ($arguments) {
            $this->definitions[$id] = [$this->definitions[$id]] + [DiContainerInterface::ARGUMENTS => $arguments];
        }

        if (null !== $shared) {
            $this->definitions[$id] = [$this->definitions[$id]] + [DiContainerInterface::SHARED => $shared];
        }

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

        if (null !== $this->config?->getAutowire()) {
            $diDefinition = $this->makeDefinition($id, $definition);

            try {
                if ($diDefinition->definition instanceof \Closure) {
                    $instance = $this->config->getAutowire()
                        ->resolveInstance($this, $diDefinition->definition, $diDefinition->arguments)
                    ;

                    return $diDefinition->shared
                        ? $this->resolved[$id] = $instance
                        : $instance;
                }

                if (\is_string($diDefinition->definition)
                    && \is_a($diDefinition->definition, DiFactoryInterface::class, true)) {
                    $instance = $this->config->getAutowire()
                        ->resolveInstance($this, $diDefinition->definition, $this->resolveArgs($diDefinition->arguments))($this)
                    ;

                    return $diDefinition->shared
                        ? $this->resolved[$id] = $instance
                        : $instance;
                }

                if (\class_exists($diDefinition->id)) {
                    $instance = $this->config->getAutowire()
                        ->resolveInstance($this, $diDefinition->id, $this->resolveArgs($diDefinition->arguments))
                    ;

                    return $diDefinition->shared
                        ? $this->resolved[$id] = $instance
                        : $instance;
                }

                if (\interface_exists($diDefinition->id)) {
                    if (null === $diDefinition->definition) {
                        throw new ContainerException("Not found definition for interface [{$id}]");
                    }

                    if (isset($this->definitions[$diDefinition->definition])) { // merge argument definition
                        $diDefinition->arguments += $this->makeDefinition(
                            $diDefinition->definition,
                            $this->definitions[$diDefinition->definition]
                        )->arguments;
                    }

                    $instance = $this->config->getAutowire()
                        ->resolveInstance($this, $diDefinition->definition, $this->resolveArgs($diDefinition->arguments))
                    ;

                    return $diDefinition->shared
                        ? $this->resolved[$id] = $instance
                        : $instance;
                }
            } catch (AutowiredExceptionInterface $exception) {
                throw new ContainerException(
                    message: $exception->getMessage(),
                    code: $exception->getCode(),
                    previous: $exception->getPrevious()
                );
            }
        }

        return $this->resolved[$id] = $definition === $id
            ? $id
            : $this->getValue($definition);
    }

    protected function getValue(mixed $value): mixed
    {
        if (!\is_string($value)) {
            return $value;
        }

        if ($this->config?->isUseArrayNotationDefinition()
            && $this->config?->isArrayNotationSyntaxSyntax($value)
            && $this->makeDefinitionForArrayNotation($value)) {
            return $this->get($value);
        }

        if ($this->config?->isUseLinkContainerDefinition()
            && $key = $this->config?->getKeyFromLinkContainerSymbol($value)) {
            return $this->getValue($this->get($key));
        }

        return $this->has($value) ? $this->get($value) : $value;
    }

    protected function hasArrayNotation(string $id): bool
    {
        try {
            return $this->config?->isArrayNotationSyntaxSyntax($id)
                && $this->makeDefinitionForArrayNotation($id);
        } catch (NotFoundException) {
            return false;
        }
    }

    protected function makeDefinitionForArrayNotation(string $id): bool
    {
        if (!isset($this->definitions[$id])) {
            $symbolNotation = $this->config?->getDelimiterAccessArrayNotationSymbol()
                ?? throw new ContainerException('Delimiter access array notation symbol not defined'); // @codeCoverageIgnore
            $offset = $this->linkContainerSymbolLength
                ?? throw new ContainerException('Link container symbol not defined'); // @codeCoverageIgnore
            $path = \explode($symbolNotation, \substr($id, $offset));
            $this->definitions[$id] = \array_reduce(
                $path,
                static function (mixed $segments, string $segment) use ($id) {
                    return isset($segments[$segment]) && \is_array($segments)
                        ? $segments[$segment]
                        : throw new NotFoundException("Unresolvable dependency: array notation key [{$id}]");
                },
                $this->definitions
            );

            return true;
        }

        return true;
    }

    protected function resolveArgs(array $constructorArgs): array
    {
        foreach ($constructorArgs as $argName => $argValue) {
            $constructorArgs[$argName] = $this->getValue($argValue);
        }

        return $constructorArgs;
    }

    protected function hasClassOrInterface(string $id): bool
    {
        return $this->config?->isUseZeroConfigurationDefinition()
            && null !== $this->config?->getAutowire()
            && (\class_exists($id) || \interface_exists($id));
    }

    protected function makeDefinition(string $id, mixed $rawDefinition): DiContainerDefinition
    {
        if (!isset($this->definitionCache[$id])) {
            $sharedDefault = $this->config?->isSharedServiceDefault() ?? false;

            if ($rawDefinition instanceof \Closure) {
                return $this->definitionCache[$id] = new DiContainerDefinition($id, $rawDefinition, $sharedDefault);
            }

            if (\is_array($rawDefinition)) {
                return $this->definitionCache[$id] = new DiContainerDefinition(
                    $id,
                    $rawDefinition[0] ?? $id,
                    $rawDefinition[DiContainerInterface::SHARED] ?? $sharedDefault,
                    $rawDefinition[DiContainerInterface::ARGUMENTS] ?? []
                );
            }

            return $this->definitionCache[$id] = new DiContainerDefinition($id, $rawDefinition, $sharedDefault);
        }

        return $this->definitionCache[$id];
    }
}
