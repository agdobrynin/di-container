<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;
use Kaspi\DiContainer\Exception\AutowireAttributeException;

use function trim;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class TaggedAs
{
    /**
     * @param non-empty-string       $name                  tag name
     * @param null|non-empty-string  $priorityDefaultMethod priority from class::method()
     * @param null|non-empty-string  $key                   identifier of definition from meta-data
     * @param null|non-empty-string  $keyDefaultMethod      if $keyFromOptions not found try get it from class::method()
     * @param list<non-empty-string> $containerIdExclude    exclude container identifiers from collection
     * @param bool                   $selfExclude           exclude the php calling class from the collection
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $isLazy = true,
        public readonly ?string $priorityDefaultMethod = null,
        public readonly bool $useKeys = true,
        public readonly ?string $key = null,
        public readonly ?string $keyDefaultMethod = null,
        public readonly array $containerIdExclude = [],
        public readonly bool $selfExclude = true,
    ) {
        if ('' === trim($name)) {
            throw new AutowireAttributeException('The $name parameter must be a non-empty string.');
        }
    }
}
