<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeServiceInterface;
use Kaspi\DiContainer\Traits\ArgumentsInAttribute;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
final class Inject implements DiAttributeServiceInterface
{
    use ArgumentsInAttribute;

    /**
     * @param class-string $id class name
     */
    public function __construct(private string $id = '', array $arguments = [], private bool $isSingleton = false)
    {
        $this->arguments = $arguments;
    }

    public function getIdentifier(): string
    {
        return $this->id;
    }

    public function isSingleton(): bool
    {
        return $this->isSingleton;
    }
}
