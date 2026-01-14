<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Exception\DiDefinitionException;

interface DiDefinitionLinkInterface extends DiDefinitionInterface
{
    /**
     * @return non-empty-string
     *
     * @throws DiDefinitionException
     */
    public function getDefinition(): string;
}
