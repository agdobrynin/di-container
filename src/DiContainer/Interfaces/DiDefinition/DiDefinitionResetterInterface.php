<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiDefinitionResetterInterface extends DiDefinitionResetterSetterInterface
{
    /**
     *  The return value may contain:
     *   - a non-empty string as the name of the method that performs cleanup for the object
     *   - `callable` expression that get the object from container and  performs cleanup for this object
     *   - the value `false` means that the resetter is not defined.
     *
     * @return callable(object $object): void|false|non-empty-string
     */
    public function getResetter(): callable|false|string;
}
