<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowiredAttributeException;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
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

    /**
     * @return array<int, DiFactory>
     */
    public static function makeFromReflection(\ReflectionClass|\ReflectionParameter $parameter): array
    {
        $attributes = $parameter->getAttributes(self::class);

        if ($parameter instanceof \ReflectionClass && \count($attributes) > 1) {
            throw new AutowiredAttributeException('The attribute #[DiFactory] can only be applied once per class.');
        }

        if ($parameter instanceof \ReflectionParameter && !$parameter->isVariadic() && \count($attributes) > 1) {
            throw new AutowiredAttributeException('The attribute #[DiFactory] can only be applied once per non-variadic parameter.');
        }

        return \array_map(static fn (\ReflectionAttribute $attribute) => $attribute->newInstance(), $attributes);
    }
}
