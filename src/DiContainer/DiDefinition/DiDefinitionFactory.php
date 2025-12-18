<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionFactoryInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\SetupConfigureTrait;
use Psr\Container\ContainerExceptionInterface;
use ReflectionMethod;

use function is_a;
use function sprintf;

final class DiDefinitionFactory implements DiDefinitionFactoryInterface, DiDefinitionIdentifierInterface, DiDefinitionSetupAutowireInterface
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

    private ArgumentBuilderInterface $factoryMethodArgumentBuilder;

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

    public function getFactoryAutowire(): DiDefinitionAutowireInterface
    {
        return $this->createFactoryAutowire();
    }

    public function exposeFactoryMethodArgumentBuilder(DiContainerInterface $container): ArgumentBuilderInterface
    {
        if (isset($this->factoryMethodArgumentBuilder)) {
            return $this->factoryMethodArgumentBuilder;
        }

        $reflectionMethod = new ReflectionMethod($this->getDefinition(), $this->getFactoryMethod());

        return $this->factoryMethodArgumentBuilder = new ArgumentBuilder([], $reflectionMethod, $container);
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

    public function getFactoryMethod(): string
    {
        $this->getDefinition();

        return '__invoke';
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): mixed
    {
        try {
            /** @var DiFactoryInterface $object */
            $object = $this->createFactoryAutowire()->resolve($container, $this);
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
     * @return non-empty-string
     */
    public function getIdentifier(): string
    {
        return $this->definition;
    }

    private function createFactoryAutowire(): DiDefinitionAutowire
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
