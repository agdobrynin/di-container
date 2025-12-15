<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionCallableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use ReflectionFunction;
use ReflectionMethod;

use function explode;
use function is_array;
use function is_string;
use function strpos;

final class DiDefinitionCallable implements DiDefinitionCallableInterface, DiDefinitionArgumentsInterface, DiTaggedDefinitionInterface, DiDefinitionTagArgumentInterface
{
    use BindArgumentsTrait {
        bindArguments as private bindArgumentsInternal;
    }
    use TagsTrait;

    /**
     * @var callable
     */
    private $definition;

    private ArgumentBuilderInterface $argBuilder;

    public function __construct(callable $definition, private readonly ?bool $isSingleton = null)
    {
        $this->definition = $definition;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function bindArguments(mixed ...$argument): static
    {
        unset($this->argBuilder);
        $this->bindArgumentsInternal(...$argument);

        return $this;
    }

    public function exposeArgumentBuilder(DiContainerInterface $container): ArgumentBuilderInterface
    {
        return new ArgumentBuilder($this->bindArguments, $this->reflectionFunction(), $container);
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): mixed
    {
        $this->argBuilder ??= $this->exposeArgumentBuilder($container);

        return ($this->definition)(...ArgumentResolver::resolve($this->argBuilder, $container, $this));
    }

    public function getDefinition(): callable
    {
        return $this->definition;
    }

    private function reflectionFunction(): ReflectionFunction|ReflectionMethod
    {
        return match (true) {
            is_array($this->definition) => new ReflectionMethod(...$this->definition), // @phpstan-ignore argument.type
            is_string($this->definition) && (strpos($this->definition, '::') > 0) => new ReflectionMethod(...explode('::', $this->definition, 2)),
            default => new ReflectionFunction($this->definition) // @phpstan-ignore argument.type
        };
    }
}
