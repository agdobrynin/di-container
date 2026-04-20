<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class ParameterRuntime
{
    public function __construct(public readonly string $name = '', public readonly ?string $message = null) {}
}
