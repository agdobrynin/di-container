<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;
use Kaspi\DiContainer\Traits\ResetterTrait;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class DiRuntime
{
    use ResetterTrait;

    /**
     * @param class-string|string                                   $containerIdentifier
     * @param null|list<Tag>|Tag                                    $tags                tags bound to the current `DiRuntime` attribute
     * @param callable(object $object): void|false|non-empty-string $resetter            provides a reset mechanism for an object obtained via the container's `get()` method
     */
    public function __construct(
        public readonly string $containerIdentifier = '',
        public readonly ?string $message = null,
        public readonly array|Tag|null $tags = null,
        callable|false|string $resetter = false,
    ) {
        $this->resetter = $resetter;
    }
}
