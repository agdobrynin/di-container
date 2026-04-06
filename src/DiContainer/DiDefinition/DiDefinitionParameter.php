<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionParameterInterface;
use ReflectionParameter;
use UnitEnum;

use function get_debug_type;
use function sprintf;

final class DiDefinitionParameter implements DiDefinitionParameterInterface
{
    public function __construct(private readonly string $name) {}

    public function getDefinition(): string
    {
        return $this->name;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): array|bool|float|int|string|UnitEnum|null
    {
        $name = match (true) {
            '' !== $this->name => $this->name,
            $context instanceof ReflectionParameter => $context->getName(),
            default => throw new DiDefinitionException(
                sprintf('Parameter name must be non-empty string. Parameter name my be pass through parameter $context with type \ReflectionParameter. Current $context type "%s"', get_debug_type($context))
            ),
        };

        return $container->parameters()->get($name);
    }
}
