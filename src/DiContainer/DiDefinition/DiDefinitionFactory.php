<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSingletonInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\SetupConfigureTrait;
use Psr\Container\ContainerExceptionInterface;

use function is_a;
use function sprintf;

final class DiDefinitionFactory implements DiDefinitionSingletonInterface, DiDefinitionIdentifierInterface, DiDefinitionSetupAutowireInterface
{
    use BindArgumentsTrait {
        bindArguments as private bindArgumentsInternal;
    }
    use SetupConfigureTrait {
        setup as private setupInternal;
        setupImmutable as private setupImmutableInternal;
    }

    private DiDefinitionAutowire $autowire;

    /**
     * @var class-string<DiFactoryInterface>
     */
    private string $verifiedDefinition;

    /**
     * @param class-string<DiFactoryInterface> $definition
     */
    public function __construct(private readonly string $definition, private readonly ?bool $isSingleton = null) {}

    public function setup(string $method, mixed ...$argument): static
    {
        unset($this->autowire);
        $this->setupInternal($method, ...$argument);

        return $this;
    }

    public function bindArguments(mixed ...$argument): static
    {
        unset($this->autowire);
        $this->bindArgumentsInternal(...$argument);

        return $this;
    }

    public function setupImmutable(string $method, mixed ...$argument): static
    {
        unset($this->autowire);
        $this->setupImmutableInternal($method, ...$argument);

        return $this;
    }

    public function exposeArgumentBuilder(DiContainerInterface $container): ?ArgumentBuilderInterface
    {
        return $this->getFactoryAutowire()->exposeArgumentBuilder($container);
    }

    public function exposeSetupArgumentBuilders(DiContainerInterface $container): array
    {
        return $this->getFactoryAutowire()->exposeSetupArgumentBuilders($container);
    }

    /**
     * @return class-string<DiFactoryInterface>
     */
    public function getDefinition(): string
    {
        if (isset($this->verifiedDefinition)) {
            return $this->verifiedDefinition;
        }

        if (!is_a($this->definition, DiFactoryInterface::class, true)) { // @phpstan-ignore function.alreadyNarrowedType
            throw new DiDefinitionException(
                sprintf('Parameter $definition for %s::__construct() must be present as class-string. Class must have implement "%s" interface. Got: "%s".', self::class, DiFactoryInterface::class, $this->definition)
            );
        }

        return $this->verifiedDefinition = $this->definition;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): mixed
    {
        try {
            /** @var DiFactoryInterface $object */
            $object = $this->getFactoryAutowire()->resolve($container, $this);
        } catch (ContainerExceptionInterface $e) {
            throw new DiDefinitionException(
                message: sprintf('Cannot resolve factory class "%s".', $this->getDefinition()),
                previous: $e
            );
        }

        return $object($container);
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    /**
     * @return class-string<DiFactoryInterface>
     */
    public function getIdentifier(): string
    {
        return $this->definition;
    }

    private function getFactoryAutowire(): DiDefinitionAutowire
    {
        if (isset($this->autowire)) {
            return $this->autowire;
        }

        $autowire = new DiDefinitionAutowire($this->getDefinition());
        $autowire->bindArguments(...$this->getBindArguments());
        $this->copySetupToDefinition($autowire);

        return $this->autowire = $autowire;
    }
}
