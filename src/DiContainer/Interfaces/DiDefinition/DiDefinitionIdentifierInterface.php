<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

interface DiDefinitionIdentifierInterface
{
    /**
     * @throws DiDefinitionExceptionInterface
     */
    public function getIdentifier(): string;
}
