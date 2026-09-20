<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DTO;

use Kaspi\DiContainer\Attributes\Tag;

/**
 * @internal
 */
final class PriorityBoundTags
{
    /**
     * @param null|list<Tag>|Tag $tags
     */
    public function __construct(public readonly array|Tag|null $tags) {}
}
