<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Interfaces\FactoryInterface;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PARAMETER)]
final class Factory
{
    /**
     * @param class-string<FactoryInterface> $id
     */
    public function __construct(public string $id)
    {
        \is_a($this->id, FactoryInterface::class, true)
            || throw new \InvalidArgumentException("Parameter '{$this->id}' must be a '".FactoryInterface::class."' interface");
    }

    public static function makeFromReflection(\ReflectionParameter $parameter): ?self
    {
        $attributes = $parameter->getAttributes(self::class);

        return [] === $attributes
            ? null
            : $attributes[0]->newInstance();
    }
}
