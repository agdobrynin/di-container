<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;
use Kaspi\DiContainer\Traits\SetupTrait;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class SetupImmutable implements DiAttributeInterface
{
    use SetupTrait;
}
