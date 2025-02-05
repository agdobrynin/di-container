<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class Tag implements DiAttributeInterface
{
    /**
     * @param non-empty-string $name tag name
     */
    public function __construct(private string $name, private array $options = [], private ?int $priority = null, private ?string $priorityTaggedMethod = null)
    {
        if ('' === \trim($name)) {
            throw new AutowireAttributeException('The $name parameter must be a non-empty string.');
        }
    }

    public function getIdentifier(): string
    {
        return $this->name;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function getPriorityTaggedMethod(): ?string
    {
        return $this->priorityTaggedMethod;
    }
}
