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
     * @param array<non-empty-string|non-negative-int, DiDefinitionType|mixed> $arguments
     */
    public function __construct(public readonly string $id = '', public readonly ?bool $isSingleton = null, public readonly array $arguments = []) {}
}
