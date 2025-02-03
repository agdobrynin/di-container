<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\CallableParserTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class DiDefinitionCallable implements DiDefinitionArgumentsInterface, DiDefinitionInvokableInterface, DiTaggedDefinitionInterface
{
    use BindArgumentsTrait;
    use CallableParserTrait;
    use ParametersResolverTrait;
    use DiContainerTrait;
    use TagsTrait;

    private $definition;

    /**
     * @var callable
     */
    private $parsedDefinition;

    /**
     * @var \ReflectionParameter[]
     */
    private array $reflectedFunctionParameters;

    public function __construct(array|callable|string $definition, private ?bool $isSingleton = null)
    {
        $this->definition = $definition;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws DiDefinitionCallableExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws AutowireExceptionInterface
     */
    public function invoke(): mixed
    {
        $this->reflectedFunctionParameters ??= $this->reflectParameters();

        if ([] === $this->reflectedFunctionParameters) {
            return \call_user_func($this->parsedDefinition);
        }

        return \call_user_func_array($this->parsedDefinition, $this->resolveParameters($this->getBindArguments(), $this->reflectedFunctionParameters));
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
