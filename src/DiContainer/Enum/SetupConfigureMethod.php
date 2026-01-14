<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Enum;

use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Interfaces\Attributes\DiSetupAttributeInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\SetupConfigureDiDefinitionInterface;

enum SetupConfigureMethod implements SetupConfigureDiDefinitionInterface
{
    case Mutable;
    case Immutable;

    public static function fromAttribute(DiSetupAttributeInterface $attribute): static
    {
        return $attribute instanceof Setup
            ? self::Mutable
            : self::Immutable;
    }
}
