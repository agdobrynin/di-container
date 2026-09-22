<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DTO;

use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Traits\ResetterTrait;

/**
 * @internal
 *
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 */
final class AutowirePriorityBoundConfiguration
{
    use ResetterTrait;

    /**
     * @param array<non-empty-string|non-negative-int, DiDefinitionType|mixed>              $arguments
     * @param null|array<non-empty-string, list<Setup|SetupImmutable>|Setup|SetupImmutable> $setups
     * @param null|list<Tag>|Tag                                                            $tags
     * @param callable(object $object): void|false|non-empty-string                         $resetter
     */
    public function __construct(public readonly array $arguments, public readonly ?array $setups, public readonly array|Tag|null $tags, callable|false|string $resetter)
    {
        $this->resetter = $resetter;
    }
}
