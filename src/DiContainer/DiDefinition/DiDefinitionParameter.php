<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionParameterInterface;
use UnitEnum;

use function get_debug_type;
use function is_string;
use function sprintf;

final class DiDefinitionParameter implements DiDefinitionNoArgumentsInterface, DiDefinitionParameterInterface
{
    private ?string $context = null;

    public function __construct(private readonly string $name = '') {}

    public function getDefinition(): string
    {
        return $this->name;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(?string $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): array|bool|float|int|string|UnitEnum|null
    {
        if ('' !== $this->name) {
            return $container->parameters()->get($this->name);
        }

        if (null !== $this->context && '' !== $this->context) {
            return $container->parameters()->get($this->context);
        }

        if (is_string($context) && '' !== $context) {
            return $container->parameters()->get($context);
        }

        throw new DiDefinitionException(
            sprintf('Parameter name must be non-empty string. Parameter name my be set as $context in DiDefinitionParameter::resolve() or in DiDefinitionParameter::setContext(). Current $context type "%s".', get_debug_type($context))
        );
    }
}
