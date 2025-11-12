<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Reflection;

use ReflectionMethod;

final class ReflectionMethodByDefinition extends ReflectionMethod
{
    public function __construct(public readonly object|string $objectOrClassName, public readonly string $method)
    {
        parent::__construct($objectOrClassName, $method);
    }
}
