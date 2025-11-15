<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSingletonInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;

use function is_a;
use function sprintf;

final class DiDefinitionFactory implements DiDefinitionSingletonInterface, DiDefinitionIdentifierInterface, DiDefinitionNoArgumentsInterface
{
    private DiDefinitionAutowire $autowire;

    /**
     * @var class-string<DiFactoryInterface>
     */
    private string $verifiedDefinition;

    /**
     * @param class-string<DiFactoryInterface> $definition
     */
    public function __construct(private readonly string $definition, private readonly ?bool $isSingleton = null) {}

    /**
     * @return class-string<DiFactoryInterface>
     */
    public function getDefinition(): string
    {
        // @phpstan-ignore function.alreadyNarrowedType
        return $this->verifiedDefinition ??= is_a($this->definition, DiFactoryInterface::class, true)
            ? $this->definition
            : throw new AutowireException(sprintf('Definition must be present as class-string. Class must have implement "%s" interface. Got: "%s".', DiFactoryInterface::class, $this->definition));
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): mixed
    {
        $this->autowire ??= new DiDefinitionAutowire($this->getDefinition());

        /** @var DiFactoryInterface $object */
        $object = $this->autowire->resolve($container, $this);

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
}
