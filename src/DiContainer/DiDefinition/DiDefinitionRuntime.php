<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionRuntimeInterface;

use function rtrim;
use function sprintf;
use function var_export;

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
            $additionalMessage = $this->message ?? sprintf('You should replace the value of definition in the runtime container using the method DiContainerInterface::set(%s, $objectInstance).', var_export($this->containerIdentifier, true));

            throw new DiDefinitionException(
                rtrim(
                    sprintf('The runtime definition with container identifier %s cannot be resolved. %s', var_export($this->containerIdentifier, true), $additionalMessage)
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
