<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\Attributes\DiSetupAttributeInterface;
use UnitEnum;

interface DiDefinitionSetupConfigureInterface extends UnitEnum
{
    public static function fromAttribute(DiSetupAttributeInterface $attribute): static;
}
