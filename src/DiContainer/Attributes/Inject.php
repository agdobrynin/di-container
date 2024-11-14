<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowiredAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;
use Kaspi\DiContainer\ParameterTypeResolverTrait;
use Psr\Container\ContainerInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
final class Inject implements DiAttributeInterface
{
    use ParameterTypeResolverTrait;

    /**
     * @param class-string|string $id class name or container reference
     */
    public function __construct(private string $id = '', private array $arguments = [], private bool $isSingleton = false) {}

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
     * @return \Generator<Inject>
     */
    public static function makeFromReflection(\ReflectionParameter $parameter, ContainerInterface $container): \Generator
    {
        $attributes = $parameter->getAttributes(self::class);

        if ([] === $attributes) {
            return;
        }

        if (!$parameter->isVariadic() && \count($attributes) > 1) {
            throw new AutowiredAttributeException('The attribute #[Inject] can only be applied once per non-variadic parameter.');
        }

        foreach ($attributes as $attribute) {
            $inject = $attribute->newInstance();

            if ('' === $inject->id && $type = self::getParameterType($parameter, $container)?->getName()) {
                $inject->id = $type;
            }

            yield $inject;
        }
    }
}
