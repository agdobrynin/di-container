<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowiredAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeServiceInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Kaspi\DiContainer\Traits\ArgumentsInAttribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
final class DiFactory implements DiAttributeServiceInterface
{
    use ArgumentsInAttribute;

    /**
     * @param class-string<DiFactoryInterface>|non-empty-string $id
     */
    public function __construct(private string $id, array $arguments = [], private bool $isSingleton = false)
    {
        \is_a($id, DiFactoryInterface::class, true)
            || throw new AutowiredAttributeException("Parameter '{$id}' must be implement '".DiFactoryInterface::class."' interface");
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
