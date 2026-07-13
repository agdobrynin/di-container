<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

/**
 * Provides a reset mechanism for container definition.
 *
 * In some long-running processes, container definitions need to be reset
 * on every request cycle to prevent memory leaks or to clean up previous changes.
 */
interface DiDefinitionResetterSetterInterface
{
    /**
     * The parameter `$resetter` can be present:
     *  - a non-empty string as the name of the method that performs cleanup for the object
     *  - `callable` expression that get the object from container and  performs cleanup for this object
     *  - boolean value `false` — resetter undefined
     *
     * @param callable(object $object): void|false|non-empty-string $resetter
     *
     * @return $this
     *
     * @throws DiDefinitionExceptionInterface
     */
    public function setResetter(callable|false|string $resetter): self;
}
