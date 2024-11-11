<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;

final class DiDefinitionCallable implements DiDefinitionAutowireInterface
{
    use ParametersResolverTrait;

    /**
     * @var callable
     */
    private $definition;

    public function __construct(callable $definition, private bool $isSingleton, array $arguments = [])
    {
        $this->definition = $definition;
        $this->reflectionParameters = $this->reflectParameters();
        $this->arguments = $arguments;
    }

    public function isSingleton(): bool
    {
        return $this->isSingleton;
    }

    public function invoke(DiContainerInterface $container, ?bool $useAttribute): mixed
    {
        $resolvedArgs = $this->resolveParameters($container, $useAttribute);
        $args = \array_values($resolvedArgs);

        return \call_user_func_array($this->definition, $args);
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
