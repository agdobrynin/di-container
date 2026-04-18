<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionParameterRuntimeInterface;
use UnitEnum;

use function get_debug_type;
use function is_string;
use function rtrim;
use function sprintf;

final class DiDefinitionParameterRuntime implements DiDefinitionParameterRuntimeInterface, DiDefinitionNoArgumentsInterface
{
    private ?string $context = null;
    private readonly string $message;

    public function __construct(
        private readonly string $name = '',
        ?string $message = null,
    ) {
        $this->message = $message ?? 'Did you forget to define it?';
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function getDefinition(): string
    {
        return $this->name;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): array|bool|float|int|string|UnitEnum|null
    {
        $parameterName = $this->nameWithContext($context);

        if ('' === $parameterName) {
            throw new DiDefinitionException(
                sprintf('Parameter name must be non-empty string. Parameter name my be set as $context in DiDefinitionParameter::resolve() or in DiDefinitionParameter::setContext(). Current $context type "%s".', get_debug_type($context))
            );
        }

        if (!$container->parameters()->has($parameterName)) {
            throw new DiDefinitionException(
                rtrim(
                    sprintf('The container parameter "%s" must be set in the container at runtime using DiContainerInterface::parameters(). %s', $parameterName, $this->message)
                )
            );
        }

        return $container->parameters()->get($parameterName);
    }

    public function setContext(?string $context): static
    {
        $this->context = $context;

        return $this;
    }

    private function nameWithContext(mixed $context): string
    {
        if ('' !== $this->name) {
            return $this->name;
        }

        if (is_string($this->context) && '' !== $this->context) {
            return $this->context;
        }

        return is_string($context) && '' !== $context
            ? $context
            : '';
    }
}
