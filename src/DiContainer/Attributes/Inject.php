<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Psr\Container\ContainerInterface;

use function Kaspi\DiContainer\Function\getParameterType;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Inject
{
    /**
     * @phan-suppress-next-next-line PhanTypeMismatchDeclaredParamNullable
     *
     * @param class-string|string $id class name or container reference
     */
    public function __construct(public ?string $id = null, public array $arguments = [], public bool $isSingleton = false) {}

    public static function makeFromReflection(\ReflectionParameter $parameter, ContainerInterface $container): ?self
    {
        if ($attribute = $parameter->getAttributes(self::class)[0] ?? null) {
            $inject = $attribute->newInstance();

            if (null === $inject->id) {
                $inject->id = getParameterType($parameter, $container)?->getName();
            }

            return $inject;
        }

        return null;
    }
}
