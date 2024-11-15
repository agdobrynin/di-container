<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Exception\AutowiredAttributeException;

use function Kaspi\DiContainer\getParameterReflectionType;

trait AttributeReaderTrait
{
    use PsrContainerTrait;

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
            $inject = $attribute->newInstance();

            if ('' === $inject->getId() && $type = getParameterReflectionType($parameter, $this->getContainer())?->getName()) {
                $inject = new Inject($type, $inject->getArguments(), $inject->isSingleton());
            }

            yield $inject;
        }
    }
}
