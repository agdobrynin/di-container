<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;
use Kaspi\DiContainer\Interfaces\Attributes\DiSetupAttributeInterface;
use Kaspi\DiContainer\Traits\SetupAttributeTrait;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Setup implements DiSetupAttributeInterface
{
    use SetupAttributeTrait;

    public function isImmutable(): bool
    {
        return false;
    }
}
