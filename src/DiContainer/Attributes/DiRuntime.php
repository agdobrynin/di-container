<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class DiRuntime
{
    /**
     * @param class-string|string $containerIdentifier
     * @param null|list<Tag>|Tag  $tags                tags bound to the current `DiRuntime` attribute
     */
    public function __construct(
        public readonly string $containerIdentifier = '',
        public readonly ?string $message = null,
        public readonly array|Tag|null $tags = null,
    ) {}
}
