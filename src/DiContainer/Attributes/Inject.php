<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Inject
{
    public function __construct(public ?string $id = null, public array $arguments = []) {}

    public static function makeFromReflection(\ReflectionParameter $parameter): ?self
    {
        $attributes = $parameter->getAttributes(self::class);

        if ([] === $attributes) {
            return null;
        }

        $inject = $attributes[0]->newInstance();
        $type = $parameter->getType();

        if (null === $inject->id && $type instanceof \ReflectionNamedType) {
            $inject->id = $type->getName();
        }

        return $inject;
    }
}
