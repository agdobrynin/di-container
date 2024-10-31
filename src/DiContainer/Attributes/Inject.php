<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Inject
{
    /**
     * @phan-suppress-next-next-line PhanTypeMismatchDeclaredParamNullable
     *
     * @param class-string|string $id class name or container reference
     */
    public function __construct(public ?string $id = null, public array $arguments = [], public bool $isSingleton = false) {}

    public static function makeFromReflection(\ReflectionParameter $parameter): ?self
    {
        if ($attribute = $parameter->getAttributes(self::class)[0] ?? null) {
            $inject = $attribute->newInstance();

            if (null === $inject->id && ($type = $parameter->getType()) && $type instanceof \ReflectionNamedType) {
                $inject->id = $type->getName();
            }

            return $inject;
        }

        return null;
    }
}
