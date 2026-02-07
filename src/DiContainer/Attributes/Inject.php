<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class Inject
{
    /**
     * @param class-string|string $id class name or container identifier
     */
    public function __construct(public readonly string $id = '') {}
}
