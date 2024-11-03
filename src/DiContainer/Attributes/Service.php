<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class Service
{
    /**
     * @param class-string|string $id class name or container reference
     */
    public function __construct(public string $id, public array $arguments = [], public bool $isSingleton = false) {}

    /**
     * @return \Generator<Service>
     */
    public static function makeFromReflection(\ReflectionClass $parameter): \Generator
    {
        foreach ($parameter->getAttributes(self::class) as $item) {
            yield $item->newInstance();
        }
    }
}
