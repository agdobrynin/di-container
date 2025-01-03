<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeServiceInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class DiFactory implements DiAttributeServiceInterface
{
    /**
     * @param class-string<DiFactoryInterface>|non-empty-string $id
     */
    public function __construct(private string $id, private bool $isSingleton = false)
    {
        if (!\is_a($id, DiFactoryInterface::class, true)) {
            throw new AutowireAttributeException('The attribute #['.self::class.'] must have an $id parameter as class-string. Class must have implement '.DiFactoryInterface::class.' interface. Got: \''.$id.'\'');
        }
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
