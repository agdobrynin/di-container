<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PARAMETER)]
final class Service
{
    public function __construct(public string $id, public array $arguments = []) {}

    public static function makeFromReflection(\ReflectionParameter $parameter): ?self
    {
        if ($attribute = $parameter->getAttributes(self::class)[0] ?? null) {
            \interface_exists((string) $parameter->getType())
                || throw new \InvalidArgumentException('Service is not implemented. Got: '.\var_export($parameter, true));

            return $attribute->newInstance();
        }

        return null;
    }
}
