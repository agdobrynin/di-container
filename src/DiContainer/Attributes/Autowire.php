<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;

/**
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Autowire
{
    /**
     * @var callable|false|non-empty-string
     */
    private $resetter;

    /**
     * @param array<non-empty-string|non-negative-int, DiDefinitionType|mixed>              $arguments arguments for `__constructor()` method
     * @param null|list<Tag>|Tag                                                            $tags      tags bound to the current `Autowire` attribute
     * @param null|array<non-empty-string, list<Setup|SetupImmutable>|Setup|SetupImmutable> $setups    methods for configuring service in current `Autowire` attribute
     * @param callable(object $object): void|false|non-empty-string                         $resetter  provides a reset mechanism for an object obtained via the container's `get()` method
     */
    public function __construct(
        public readonly string $id = '',
        public readonly ?bool $isSingleton = null,
        public readonly array $arguments = [],
        public readonly array|Tag|null $tags = null,
        public readonly ?array $setups = null,
        callable|false|string $resetter = false,
    ) {
        $this->resetter = $resetter;
    }

    /**
     * Provides a reset mechanism for an object obtained via the container's `get()` method.
     *
     * @return callable(object $object): void|false|non-empty-string
     */
    public function getResetter(): callable|false|string
    {
        return $this->resetter;
    }
}
