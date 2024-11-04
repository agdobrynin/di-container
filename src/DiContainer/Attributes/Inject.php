<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowiredAttributeException;
use Kaspi\DiContainer\ParameterTypeResolverTrait;
use Psr\Container\ContainerInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
final class Inject
{
    use ParameterTypeResolverTrait;

    /**
     * @phan-suppress-next-next-line PhanTypeMismatchDeclaredParamNullable
     *
     * @param class-string|string $id class name or container reference
     */
    public function __construct(public ?string $id = null, public array $arguments = [], public bool $isSingleton = false) {}

    /**
     * @return array<int, Inject>
     */
    public static function makeFromReflection(\ReflectionParameter $parameter, ContainerInterface $container): array
    {
        $attributes = $parameter->getAttributes(self::class);

        if (!$parameter->isVariadic() && \count($attributes) > 1) {
            throw new AutowiredAttributeException('The attribute #[Inject] can only be applied once per non-variadic parameter.');
        }

        return \array_filter(
            \array_map(static function (\ReflectionAttribute $attribute) use ($parameter, $container) {
                $inject = $attribute->newInstance();

                if (null === $inject->id) {
                    $inject->id = self::getParameterType($parameter, $container)?->getName();
                }

                return $inject->id ? $inject : null;
            }, $attributes)
        );
    }
}
