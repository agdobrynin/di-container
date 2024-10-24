<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowiredAttributeException;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PARAMETER)]
final class DiFactory
{
    /**
     * @param class-string<DiFactoryInterface> $id
     */
    public function __construct(public string $id, public array $arguments = [], public bool $isSingleton = false)
    {
        \is_a($this->id, DiFactoryInterface::class, true)
            || throw new AutowiredAttributeException("Parameter '{$this->id}' must be implement '".DiFactoryInterface::class."' interface");
    }

    public static function makeFromReflection(\ReflectionClass|\ReflectionParameter $parameter): ?self
    {
        $attributes = $parameter->getAttributes(self::class);

        return [] === $attributes
            ? null
            : $attributes[0]->newInstance();
    }
}
