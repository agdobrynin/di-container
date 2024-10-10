<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Inject
{
    /**
     * @param null|class-string|string $id Container id
     */
    public function __construct(public ?string $id = null, public array $arguments = [], public bool $isSingleton = false) {}

    public static function makeFromReflection(\ReflectionParameter $parameter): ?self
    {
        if ($attribute = $parameter->getAttributes(self::class)[0] ?? null) {
            $inject = $attribute->newInstance();
            $type = $parameter->getType();

            if (null === $inject->id && $type instanceof \ReflectionNamedType) {
                $inject->id = $type->getName();
            }

            return $inject;
        }

        return null;
    }
}
