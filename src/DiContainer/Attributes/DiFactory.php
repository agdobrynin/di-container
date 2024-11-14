<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowiredAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
final class DiFactory implements DiAttributeInterface
{
    /**
     * @param class-string<DiFactoryInterface> $id
     */
    public function __construct(private string $id, private array $arguments = [], private bool $isSingleton = false)
    {
        \is_a($id, DiFactoryInterface::class, true)
            || throw new AutowiredAttributeException("Parameter '{$id}' must be implement '".DiFactoryInterface::class."' interface");
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function isSingleton(): bool
    {
        return $this->isSingleton;
    }

    /**
     * @return \Generator<DiFactory>
     */
    public static function makeFromReflection(\ReflectionClass|\ReflectionParameter $parameter): \Generator
    {
        $attributes = $parameter->getAttributes(self::class);

        if ($parameter instanceof \ReflectionClass && \count($attributes) > 1) {
            throw new AutowiredAttributeException('The attribute #[DiFactory] can only be applied once per class.');
        }

        if ($parameter instanceof \ReflectionParameter && !$parameter->isVariadic() && \count($attributes) > 1) {
            throw new AutowiredAttributeException('The attribute #[DiFactory] can only be applied once per non-variadic parameter.');
        }

        foreach ($attributes as $attribute) {
            yield $attribute->newInstance();
        }
    }
}
