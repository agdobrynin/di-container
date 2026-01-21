<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;

use function trim;

/**
 * @phpstan-import-type TagOptions from DiDefinitionTagArgumentInterface
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Tag implements DiAttributeInterface
{
    /**
     * @param non-empty-string $name    tag name
     * @param TagOptions       $options tag's meta-data
     */
    public function __construct(private readonly string $name, private readonly array $options = [], private readonly int|string|null $priority = null, private readonly ?string $priorityMethod = null)
    {
        if ('' === trim($name)) {
            throw new AutowireAttributeException('The $name parameter must be a non-empty string.');
        }
    }

    /**
     * @return non-empty-string
     */
    public function getIdentifier(): string
    {
        return $this->name;
    }

    /**
     * @return TagOptions
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getPriority(): int|string|null
    {
        return $this->priority;
    }

    public function getPriorityMethod(): ?string
    {
        return $this->priorityMethod;
    }
}
