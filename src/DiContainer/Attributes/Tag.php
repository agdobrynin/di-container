<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;

use function sprintf;
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
    public function __construct(private string $name, private array $options = [], private int|string|null $priority = null, private ?string $priorityMethod = null)
    {
        if ('' === trim($name)) {
            throw new AutowireAttributeException(
                sprintf('The attribute #[%s] must have $name parameter as non-empty string.', self::class)
            );
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
