<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Exception\AutowiredAttributeException;

trait AttributeReaderTrait
{
    use ParameterTypeByReflectionTrait;

    public function getDiFactoryAttribute(\ReflectionClass $parameter): ?DiFactory
    {
        return ($attribute = $parameter->getAttributes(DiFactory::class)[0] ?? null)
            ? $attribute->newInstance()
            : null;
    }

    public function getServiceAttribute(\ReflectionClass $parameter): ?Service
    {
        return ($attribute = $parameter->getAttributes(Service::class)[0] ?? null)
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
            throw new AutowiredAttributeException(
                'The attribute #[Inject] can only be applied once per non-variadic parameter.'
            );
        }

        foreach ($attributes as $attribute) {
            /** @var Inject $inject */
            $inject = $attribute->newInstance();

            if ('' === $inject->getIdentifier()
                && $type = $this->getParameterTypeByReflection($parameter)?->getName()) {
                $inject = new Inject($type);
            }

            yield $inject;
        }
    }
}
