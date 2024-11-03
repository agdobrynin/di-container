<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Service
{
    /**
     * @param class-string|string $id class name or container reference
     */
    public function __construct(public string $id, public array $arguments = [], public bool $isSingleton = false) {}

    public static function makeFromReflection(\ReflectionClass $parameter): ?self
    {
        return ($attribute = $parameter->getAttributes(self::class)[0] ?? null)
            ? $attribute->newInstance()
            : null;
    }
}
