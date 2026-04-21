<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionRuntimeInterface;

use function rtrim;
use function sprintf;

final class DiDefinitionRuntime implements DiDefinitionNoArgumentsInterface, DiDefinitionRuntimeInterface
{
    private object $definition;

    /**
     * @param class-string|non-empty-string $containerIdentifier
     */
    public function __construct(
        private readonly string $containerIdentifier,
        private readonly ?string $message = null,
    ) {}

    public function getIdentifier(): string
    {
        return $this->containerIdentifier;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): object
    {
        if (!isset($this->definition)) {
            $additionalMessage = $this->message ?? 'You should replace the value of "runtime definition" in the runtime container using the DiContainerInterface::set() method.';

            throw new DiDefinitionException(
                rtrim(
                    sprintf('The "runtime definition" cannot be resolved. %s', $additionalMessage)
                )
            );
        }

        return $this->definition;
    }

    public function getDefinition(): ?object
    {
        return $this->definition ?? null;
    }

    public function setDefinition(object $definition): void
    {
        $this->definition = $definition;
    }
}
