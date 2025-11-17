<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Enum;

use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Interfaces\Attributes\DiSetupAttributeInterface;

enum SetupConfigureMethod
{
    case Mutable;
    case Immutable;

    public static function fromAttribute(DiSetupAttributeInterface $attribute): self
    {
        return $attribute instanceof Setup
            ? self::Mutable
            : self::Immutable;
    }
}
