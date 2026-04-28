<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

/**
 * Some container definitions cannot be specified in configuration files because they are evaluated at runtime.
 *
 * A definition implementing the `DiDefinitionRuntimeInterface` interface does not specify a class or object,
 * it only provides the container identifier.
 *
 * This definition should be replaced by other available definitions with the same container identifier.
 */
interface DiDefinitionRuntimeInterface extends DiDefinitionIdentifierInterface, DiDefinitionInterface
{
    public function getDefinition(): ?object;

    /**
     * Runtime definition value.
     */
    public function setDefinition(object $definition): void;

    /**
     * Additional message for definition.
     */
    public function getMessage(): ?string;
}
