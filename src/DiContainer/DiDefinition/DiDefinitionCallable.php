<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\DiDefinitionCallableException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use Kaspi\DiContainer\Interfaces\ParametersResolverInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class DiDefinitionCallable implements DiDefinitionAutowireInterface
{
    /**
     * @var callable
     */
    private $definition;

    /**
     * @var \ReflectionParameter[]
     */
    private array $reflectedParameters;
    private array $arguments;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws DiDefinitionCallableExceptionInterface
     */
    public function __construct(
        private ?ContainerInterface $container,
        private string $id,
        array|callable|string $definition,
        private bool $isSingleton,
        array $arguments = [],
    ) {
        $this->definition = $this->makeFromAbstract($definition);
        $this->reflectedParameters = $this->reflectParameters();
        $this->arguments = $arguments;
    }

    public function getContainerId(): string
    {
        return $this->id;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function isSingleton(): bool
    {
        return $this->isSingleton;
    }

    public function invoke(ParametersResolverInterface $parametersResolver): mixed
    {
        $resolvedArguments = $parametersResolver->resolve($this->reflectedParameters, $this->arguments);

        return \call_user_func_array($this->definition, \array_values($resolvedArguments));
    }

    public function getDefinition(): callable
    {
        return $this->definition;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws DiDefinitionCallableExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function makeFromAbstract(array|callable|string $definition): callable
    {
        if (\is_callable($definition)) {
            return $definition;
        }

        $def = $this->parseDefinition($definition);

        if (\is_string($def[0]) && $this->container) {
            $def[0] = $this->container->get($def[0]);
        }

        return \is_callable($def)
            ? $def
            : throw new DiDefinitionCallableException('Definition is not callable. Got: '.\var_export($definition, true));
    }

    private function parseDefinition(array|string $definition): array
    {
        if (\is_array($definition)) {
            isset($definition[0], $definition[1])
            || throw new DiDefinitionCallableException(
                'When the definition is an array, two array elements must be provided. Got: '.\var_export($definition, true)
            );

            return [$definition[0], $definition[1]];
        }

        if (\strpos($definition, '::') > 0) {
            return \explode('::', $definition, 2);
        }

        return [$definition, '__invoke'];
    }

    /**
     * @return \ReflectionParameter[]
     *
     * @throws \ReflectionException
     */
    private function reflectParameters(): array
    {
        if (\is_array($this->definition)) {
            return (new \ReflectionMethod($this->definition[0], $this->definition[1]))->getParameters();
        }

        if (\is_string($this->definition) && \strpos($this->definition, '::') > 0) {
            return (new \ReflectionMethod($this->definition))->getParameters();
        }

        if ($this->definition instanceof \Closure) {
            return (new \ReflectionFunction($this->definition))->getParameters();
        }

        if (\is_string($this->definition) && \function_exists($this->definition)) {
            return (new \ReflectionFunction($this->definition))->getParameters();
        }

        // @phan-suppress-next-line PhanTypeMismatchArgumentInternal
        return (new \ReflectionMethod($this->definition, '__invoke'))->getParameters();
    }
}
