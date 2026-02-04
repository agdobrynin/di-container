<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;

use function trim;

/**
 * @phpstan-import-type TagOptions from DiDefinitionTagArgumentInterface
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Tag
{
    /**
     * @param non-empty-string $name    tag name
     * @param TagOptions       $options tag's meta-data
     */
    public function __construct(
        public readonly string $name,
        public readonly array $options = [],
        public readonly int|string|null $priority = null,
        public readonly ?string $priorityMethod = null
    ) {
        if ('' === trim($name)) {
            throw new AutowireAttributeException('The $name parameter must be a non-empty string.');
        }
    }
}
