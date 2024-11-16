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
use Psr\Container\ContainerInterface;
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

    public function __construct(array|callable|string $definition, private ?bool $isSingleton = null, array $arguments = [])
    {
        $this->definition = $definition;
        $this->arguments = $arguments;
    }

    public function addArgument(string $name, mixed $value): self
    {
        $this->arguments[$name] = $value;

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
    public function invoke(ContainerInterface $container, ?bool $useAttribute): mixed
    {
        $this->reflectionParameters ??= $this->reflectParameters($container);

        if ([] === $this->reflectionParameters) {
            return \call_user_func($this->parsedDefinition);
        }

        $this->setContainer($container);
        $resolvedArgs = $this->resolveParameters($useAttribute);

        return \call_user_func_array($this->parsedDefinition, $resolvedArgs);
    }

    public function getDefinition(): array|callable|string
    {
        return $this->definition;
    }

    /**
     * @return \ReflectionParameter[]
     *
     * @throws ContainerExceptionInterface
     * @throws DiDefinitionCallableExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function reflectParameters(ContainerInterface $container): array
    {
        $this->setContainer($container);
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
