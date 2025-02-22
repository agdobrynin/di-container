<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
final class TaggedAs implements DiAttributeInterface
{
    /**
     * @param non-empty-string       $name                  tag name
     * @param null|non-empty-string  $priorityDefaultMethod priority from class::method()
     * @param null|non-empty-string  $key                   identifier of definition from meta-data
     * @param null|non-empty-string  $keyDefaultMethod      if $keyFromOptions not found try get it from class::method()
     * @param list<non-empty-string> $containerIdExcludes   exclude container identifiers from collection
     * @param bool                   $selfExclude           exclude the php calling class from the collection
     */
    public function __construct(
        private string $name,
        private bool $isLazy = true,
        private ?string $priorityDefaultMethod = null,
        private bool $useKeys = true,
        private ?string $key = null,
        private ?string $keyDefaultMethod = null,
        private array $containerIdExcludes = [],
        private bool $selfExclude = true,
    ) {
        if ('' === \trim($name)) {
            throw new AutowireAttributeException('The $name parameter must be a non-empty string.');
        }
    }

    /**
     * @return list<non-empty-string>
     */
    public function getContainerIdExcludes(): array
    {
        return $this->containerIdExcludes;
    }

    public function isSelfExclude(): bool
    {
        return $this->selfExclude;
    }

    /**
     * @return null|non-empty-string
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @return null|non-empty-string
     */
    public function getKeyDefaultMethod(): ?string
    {
        return $this->keyDefaultMethod;
    }

    public function isUseKeys(): bool
    {
        return $this->useKeys;
    }

    /**
     * @return non-empty-string
     */
    public function getIdentifier(): string
    {
        return $this->name;
    }

    public function isLazy(): bool
    {
        return $this->isLazy;
    }

    /**
     * @return null|non-empty-string
     */
    public function getPriorityDefaultMethod(): ?string
    {
        return $this->priorityDefaultMethod;
    }
}
