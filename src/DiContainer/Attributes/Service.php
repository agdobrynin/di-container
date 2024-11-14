<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Service implements DiAttributeInterface
{
    /**
     * @param class-string|string $id class name or container reference
     */
    public function __construct(private string $id, private array $arguments = [], private bool $isSingleton = false) {}

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

    public static function makeFromReflection(\ReflectionClass $parameter): ?self
    {
        return ($attribute = $parameter->getAttributes(self::class)[0] ?? null)
            ? $attribute->newInstance()
            : null;
    }
}
