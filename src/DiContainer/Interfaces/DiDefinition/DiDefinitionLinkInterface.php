<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

interface DiDefinitionLinkInterface extends DiDefinitionInterface
{
    /**
     * @return non-empty-string
     *
     * @throws DiDefinitionExceptionInterface
     */
    public function getDefinition(): string;
}
