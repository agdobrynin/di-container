<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiDefinitionLinkInterface extends DiDefinitionInterface
{
    /**
     * @return non-empty-string
     */
    public function getDefinition(): string;
}
