<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DTO;

use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;

/**
 * @internal
 *
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 */
final class PriorityBoundConfiguration
{
    /**
     * @param array<non-empty-string|non-negative-int, DiDefinitionType|mixed>              $arguments
     * @param null|array<non-empty-string, list<Setup|SetupImmutable>|Setup|SetupImmutable> $setups
     * @param null|list<Tag>|Tag                                                            $tags
     */
    public function __construct(public readonly array $arguments, public readonly ?array $setups, public readonly array|Tag|null $tags) {}
}
