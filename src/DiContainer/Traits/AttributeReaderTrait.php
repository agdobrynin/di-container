<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\Exception\AutowireAttributeException;

trait AttributeReaderTrait
{
    use ParameterTypeByReflectionTrait;

    protected function getDiFactoryAttribute(\ReflectionClass $reflectionClass): ?DiFactory
    {
        return ($attribute = $reflectionClass->getAttributes(DiFactory::class)[0] ?? null)
            ? $attribute->newInstance()
            : null;
    }

    protected function getServiceAttribute(\ReflectionClass $reflectionClass): ?Service
    {
        return ($attribute = $reflectionClass->getAttributes(Service::class)[0] ?? null)
            ? $attribute->newInstance()
            : null;
    }

    /**
     * @return \Generator<Inject>
     */
    protected function getInjectAttribute(\ReflectionParameter $reflectionParameter): \Generator
    {
        $attributes = $reflectionParameter->getAttributes(Inject::class);

        if ([] === $attributes) {
            return;
        }

        if (!$reflectionParameter->isVariadic() && \count($attributes) > 1) {
            throw new AutowireAttributeException(
                'The attribute #['.Inject::class.'] can only be applied once per non-variadic parameter.'
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
     * @return \Generator<ProxyClosure>
     */
    protected function getProxyClosureAttribute(\ReflectionParameter $reflectionParameter): \Generator
    {
        $attributes = $reflectionParameter->getAttributes(ProxyClosure::class);

        if ([] === $attributes) {
            return;
        }

        if (!$reflectionParameter->isVariadic() && \count($attributes) > 1) {
            throw new AutowireAttributeException(
                'The attribute #['.ProxyClosure::class.'] can only be applied once per non-variadic parameter.'
            );
        }

        foreach ($attributes as $attribute) {
            yield $attribute->newInstance();
        }
    }

    protected function getTaggedAsAttribute(\ReflectionParameter $reflectionParameter): \Generator
    {
        $attributes = $reflectionParameter->getAttributes(TaggedAs::class);

        if ([] === $attributes) {
            return;
        }

        if (!$reflectionParameter->isVariadic() && \count($attributes) > 1) {
            throw new AutowireAttributeException(
                'The attribute #['.TaggedAs::class.'] can only be applied once per non-variadic parameter.'
            );
        }

        foreach ($attributes as $attribute) {
            yield $attribute->newInstance();
        }
    }
}
