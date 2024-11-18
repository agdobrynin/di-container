<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\InjectByReference;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Attributes\ServiceByReference;
use Kaspi\DiContainer\Exception\AutowiredAttributeException;

trait AttributeReaderTrait
{
    use ParameterTypeByReflectionTrait;

    /**
     * @return \Generator<DiFactory>
     */
    public function getDiFactoryAttribute(\ReflectionClass|\ReflectionParameter $parameter): \Generator
    {
        $attributes = $parameter->getAttributes(DiFactory::class);

        if ([] === $attributes) {
            return;
        }

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

    public function getServiceAttribute(\ReflectionClass $parameter): ?Service
    {
        return ($attribute = $parameter->getAttributes(Service::class)[0] ?? null)
            ? $attribute->newInstance()
            : null;
    }

    public function getServiceByReferenceAttribute(\ReflectionClass $parameter): ?ServiceByReference
    {
        return ($attribute = $parameter->getAttributes(ServiceByReference::class)[0] ?? null)
            ? $attribute->newInstance()
            : null;
    }

    /**
     * @return \Generator<Inject>
     */
    public function getInjectAttribute(\ReflectionParameter $parameter): \Generator
    {
        $attributes = $parameter->getAttributes(Inject::class);

        if ([] === $attributes) {
            return;
        }

        if (!$parameter->isVariadic() && \count($attributes) > 1) {
            throw new AutowiredAttributeException('The attribute #[Inject] can only be applied once per non-variadic parameter.');
        }

        foreach ($attributes as $attribute) {
            /** @var Inject $inject */
            $inject = $attribute->newInstance();

            if ('' === $inject->getIdentifier()
                && $type = $this->getParameterTypeByReflection($parameter)?->getName()) {
                $inject = new Inject($type, $inject->getArguments(), $inject->isSingleton());
            }

            yield $inject;
        }
    }

    /**
     * @return \Generator<InjectByReference>
     */
    public function getInjectByReferenceAttribute(\ReflectionParameter $parameter): \Generator
    {
        $attributes = $parameter->getAttributes(InjectByReference::class);

        if ([] === $attributes) {
            return;
        }

        if (!$parameter->isVariadic() && \count($attributes) > 1) {
            throw new AutowiredAttributeException('The attribute #[InjectByReference] can only be applied once per non-variadic parameter.');
        }

        foreach ($attributes as $attribute) {
            yield $attribute->newInstance();
        }
    }
}
