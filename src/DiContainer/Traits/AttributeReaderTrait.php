<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Attributes\TagDefaultPriorityMethod;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\Exception\AutowireAttributeException;

trait AttributeReaderTrait
{
    use ParameterTypeByReflectionTrait;

    private function getDiFactoryAttribute(\ReflectionClass $reflectionClass): ?DiFactory
    {
        return ($attribute = $reflectionClass->getAttributes(DiFactory::class)[0] ?? null)
            ? $attribute->newInstance()
            : null;
    }

    private function getServiceAttribute(\ReflectionClass $reflectionClass): ?Service
    {
        return ($attribute = $reflectionClass->getAttributes(Service::class)[0] ?? null)
            ? $attribute->newInstance()
            : null;
    }

    /**
     * @return \Generator<Inject>
     */
    private function getInjectAttribute(\ReflectionParameter $reflectionParameter): \Generator
    {
        $attributes = $reflectionParameter->getAttributes(Inject::class);

        if ([] === $attributes) {
            return;
        }

        $this->checkVariadic($reflectionParameter, \count($attributes), Inject::class);

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
    private function getProxyClosureAttribute(\ReflectionParameter $reflectionParameter): \Generator
    {
        $attributes = $reflectionParameter->getAttributes(ProxyClosure::class);

        if ([] === $attributes) {
            return;
        }

        $this->checkVariadic($reflectionParameter, \count($attributes), ProxyClosure::class);

        foreach ($attributes as $attribute) {
            yield $attribute->newInstance();
        }
    }

    /**
     * @return \Generator<TaggedAs>
     */
    private function getTaggedAsAttribute(\ReflectionParameter $reflectionParameter): \Generator
    {
        $attributes = $reflectionParameter->getAttributes(TaggedAs::class);

        if ([] === $attributes) {
            return;
        }

        $this->checkVariadic($reflectionParameter, \count($attributes), TaggedAs::class);

        foreach ($attributes as $attribute) {
            yield $attribute->newInstance();
        }
    }

    /**
     * @return \Generator<Tag>
     */
    private function getTagAttribute(\ReflectionClass $reflectionClass): \Generator
    {
        $attributes = $reflectionClass->getAttributes(Tag::class);

        if ([] === $attributes) {
            return;
        }

        foreach ($attributes as $attribute) {
            yield $attribute->newInstance();
        }
    }

    private function getTagDefaultPriorityMethod(\ReflectionClass $reflectionClass): ?TagDefaultPriorityMethod
    {
        return ($attribute = $reflectionClass->getAttributes(TagDefaultPriorityMethod::class)[0] ?? null)
            ? $attribute->newInstance()
            : null;
    }

    private function checkVariadic(\ReflectionParameter $reflectionParameter, int $countAttributes, string $attribute): void
    {
        if ($countAttributes > 1 && !$reflectionParameter->isVariadic()) {
            throw new AutowireAttributeException(
                \sprintf('The attribute #[%s] can only be applied once per non-variadic parameter.', $attribute)
            );
        }
    }
}
