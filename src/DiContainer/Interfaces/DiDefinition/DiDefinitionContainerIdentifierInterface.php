<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiDefinitionContainerIdentifierInterface
{
    /**
     * The identifier that matches this definition in the container.
     *
     * @return null|non-empty-string
     */
    public function getContainerIdentifier(): ?string;

    /**
     * Sets the identifier corresponding to this definition in the container.
     *
     * @param non-empty-string $containerIdentifier
     */
    public function setContainerIdentifier(string $containerIdentifier): void;
}
