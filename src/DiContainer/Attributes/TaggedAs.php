<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
final class TaggedAs implements DiAttributeInterface
{
    /**
     * @param non-empty-string $name tag name
     */
    public function __construct(
        private string $name,
        private bool $isLazy = true,
        private ?string $defaultPriorityMethod = null,
        private bool $requireDefaultPriorityMethod = false
    ) {
        if ('' === \trim($name)) {
            throw new AutowireAttributeException('The $name parameter must be a non-empty string.');
        }
    }

    public function isRequireDefaultPriorityMethod(): bool
    {
        return $this->requireDefaultPriorityMethod;
    }

    public function getIdentifier(): string
    {
        return $this->name;
    }

    public function isLazy(): bool
    {
        return $this->isLazy;
    }

    public function getDefaultPriorityMethod(): ?string
    {
        return $this->defaultPriorityMethod;
    }
}
