<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Enum;

enum InvalidBehaviorCompileEnum
{
    /**
     * Throw an exception during container compilation.
     */
    case ExceptionOnCompile;

    /**
     * Throw an exception when compiled container try getting entry from container.
     */
    case RuntimeContainerException;
}
