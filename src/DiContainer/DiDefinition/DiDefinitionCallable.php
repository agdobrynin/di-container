<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use Psr\Container\ContainerInterface;

final class DiDefinitionCallable implements DiDefinitionAutowireInterface
{
    use ParametersResolverTrait;
    use PsrContainerTrait;

    /**
     * @var callable
     */
    private $definition;

    public function __construct(callable $definition, private bool $isSingleton, array $arguments = [])
    {
        $this->definition = $definition;
        $this->arguments = $arguments;
    }

    public function isSingleton(): bool
    {
        return $this->isSingleton;
    }

    public function invoke(ContainerInterface $container, ?bool $useAttribute): mixed
    {
        $this->setContainer($container);

        if (!isset($this->reflectionParameters)) {
            $this->reflectionParameters = $this->reflectParameters();
        }

        if ([] === $this->reflectionParameters) {
            return \call_user_func($this->definition);
        }

        $resolvedArgs = $this->resolveParameters($useAttribute);

        return \call_user_func_array($this->definition, $resolvedArgs);
    }

    public function getDefinition(): callable
    {
        return $this->definition;
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

        // @phan-suppress-next-line PhanTypeMismatchArgumentInternal
        return (new \ReflectionFunction($this->definition))->getParameters();
    }
}
