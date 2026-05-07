<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class DiRuntime
{
    public function __construct(public readonly string $containerIdentifier = '', public readonly ?string $message = null) {}
}
