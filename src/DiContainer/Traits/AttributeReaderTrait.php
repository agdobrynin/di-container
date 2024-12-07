<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Attributes\AsClosure;
use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Exception\AutowireAttributeException;

trait AttributeReaderTrait
{
    use ParameterTypeByReflectionTrait;

    public function getDiFactoryAttribute(\ReflectionClass $reflectionClass): ?DiFactory
    {
        return ($attribute = $reflectionClass->getAttributes(DiFactory::class)[0] ?? null)
            ? $attribute->newInstance()
            : null;
    }

    public function getServiceAttribute(\ReflectionClass $reflectionClass): ?Service
    {
        return ($attribute = $reflectionClass->getAttributes(Service::class)[0] ?? null)
            ? $attribute->newInstance()
            : null;
    }

    /**
     * @return \Generator<Inject>
     */
    public function getInjectAttribute(\ReflectionParameter $reflectionParameter): \Generator
    {
        $attributes = $reflectionParameter->getAttributes(Inject::class);

        if ([] === $attributes) {
            return;
        }

        if (!$reflectionParameter->isVariadic() && \count($attributes) > 1) {
            throw new AutowireAttributeException(
                'The attribute #[Inject] can only be applied once per non-variadic parameter.'
            );
        }

        foreach ($attributes as $attribute) {
            /** @var Inject $inject */
            $inject = $attribute->newInstance();

            if ('' === $inject->getIdentifier()
                && $type = $this->getParameterTypeByReflection($reflectionParameter)?->getName()) {
                $inject = new Inject($type);
            }

            yield $inject;
        }
    }

    /**
     * @return \Generator<AsClosure>
     */
    public function getAsClosureAttribute(\ReflectionParameter $reflectionParameter): \Generator
    {
        $attributes = $reflectionParameter->getAttributes(AsClosure::class);

        if ([] === $attributes) {
            return;
        }

        if (!$reflectionParameter->isVariadic() && \count($attributes) > 1) {
            throw new AutowireAttributeException(
                'The attribute #[AsClosure] can only be applied once per non-variadic parameter.'
            );
        }

        foreach ($attributes as $attribute) {
            yield $attribute->newInstance();
        }
    }
}
