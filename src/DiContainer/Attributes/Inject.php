<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Inject
{
    public function __construct(public ?string $id = null, public array $arguments = []) {}

    public static function resolve(\ReflectionParameter $parameter): ?self
    {
        if ($attribute = $parameter->getAttributes(self::class)[0] ?? null) {
            $inject = $attribute->newInstance();

            if (null === $inject->id) {
                $inject->id = $parameter->getType()?->getName();
            }

            return $inject;
        }

        return null;
    }
}
