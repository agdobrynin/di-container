<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use Kaspi\DiContainer\Traits\CallableParserTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class DiDefinitionCallable implements DiDefinitionAutowireInterface
{
    use CallableParserTrait;
    use ParametersResolverTrait;
    use PsrContainerTrait;

    private $definition;

    /**
     * @var callable
     */
    private $parsedDefinition;

    public function __construct(array|callable|string $definition, private ?bool $isSingleton = null)
    {
        $this->definition = $definition;
    }

    public function addArgument(string $name, mixed $value): static
    {
        $this->arguments[$name] = $value;

        return $this;
    }

    public function addArguments(array $arguments): static
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws DiDefinitionCallableExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws AutowiredExceptionInterface
     */
    public function invoke(?bool $useAttribute = null): mixed
    {
        $this->reflectionParameters ??= $this->reflectParameters();

        if ([] === $this->reflectionParameters) {
            return \call_user_func($this->parsedDefinition);
        }

        $resolvedArgs = $this->resolveParameters($useAttribute);

        return \call_user_func_array($this->parsedDefinition, $resolvedArgs);
    }

    public function getDefinition(): callable
    {
        return $this->parsedDefinition ??= $this->parseCallable($this->definition);
    }

    /**
     * @return \ReflectionParameter[]
     *
     * @throws ContainerExceptionInterface
     * @throws DiDefinitionCallableExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function reflectParameters(): array
    {
        $this->parsedDefinition ??= $this->parseCallable($this->definition);

        if (\is_array($this->parsedDefinition)) {
            return (new \ReflectionMethod($this->parsedDefinition[0], $this->parsedDefinition[1]))->getParameters();
        }

        if (\is_string($this->parsedDefinition) && \strpos($this->parsedDefinition, '::') > 0) {
            return (new \ReflectionMethod($this->parsedDefinition))->getParameters();
        }

        // @phan-suppress-next-line PhanPartialTypeMismatchArgumentInternal
        return (new \ReflectionFunction($this->parsedDefinition))->getParameters();
    }
}
