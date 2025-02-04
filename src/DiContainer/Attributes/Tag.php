<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class Tag implements DiAttributeInterface
{
    private array $normalizedOptions;

    /**
     * @param non-empty-string $name tag name
     */
    public function __construct(
        private string $name,
        private array $options = ['priority' => 0],
        private ?int $priority = null,
        private ?string $priorityMethod = null
    ) {
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
        if (!isset($this->normalizedOptions)) {
            $this->normalizedOptions = $this->options;

            if (null !== $this->priority) {
                $this->normalizedOptions['priority'] = $this->priority;
            }

            if (null !== $this->priorityMethod) {
                $this->normalizedOptions['priorityMethod'] = $this->priorityMethod;
            }
        }

        return $this->normalizedOptions;
    }
}
