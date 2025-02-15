<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
final class TaggedAs implements DiAttributeInterface
{
    /**
     * @param non-empty-string      $name                  tag name
     * @param null|non-empty-string $priorityDefaultMethod
     */
    public function __construct(
        private string $name,
        private bool $isLazy = true,
        private ?string $priorityDefaultMethod = null,
        private bool $useKeys = true,
    ) {
        if ('' === \trim($name)) {
            throw new AutowireAttributeException('The $name parameter must be a non-empty string.');
        }
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
