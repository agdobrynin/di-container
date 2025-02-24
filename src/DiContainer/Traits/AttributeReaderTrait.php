<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;

trait AttributeReaderTrait
{
    use ParameterTypeByReflectionTrait;

    private function getDiFactoryAttribute(\ReflectionClass $reflectionClass): ?DiFactory
    {
        return ($reflectionClass->getAttributes(DiFactory::class)[0] ?? null)?->newInstance();
    }

    private function getServiceAttribute(\ReflectionClass $reflectionClass): ?Service
    {
        return ($reflectionClass->getAttributes(Service::class)[0] ?? null)?->newInstance();
    }

    /**
     * @return \Generator<Inject>|\Generator<InjectByCallable>|\Generator<ProxyClosure>|\Generator<TaggedAs>
     *
     * @throws AutowireExceptionInterface
     */
    private function getAttributeOnParameter(\ReflectionParameter $reflectionParameter): \Generator
    {
        $oneOfAttributes = [Inject::class, ProxyClosure::class, TaggedAs::class, InjectByCallable::class];

        $attribute = \array_reduce(
            $reflectionParameter->getAttributes(),
            static function (array $attrs, \ReflectionAttribute $a) use ($oneOfAttributes) {
                if (\in_array($a->getName(), $oneOfAttributes, true)) {
                    $attrs[$a->getName()] = true;
                }

                return $attrs;
            },
            [],
        );

        if ([] === $attribute) {
            return;
        }

        if (\count($attribute) > 1) {
            throw new AutowireAttributeException(
                \sprintf('Only one of the attributes %s may be declared.', '#['.\implode('], #[', $oneOfAttributes).']')
            );
        }

        if (isset($attribute[Inject::class])) {
            yield from $this->getInjectAttribute($reflectionParameter);

            return;
        }

        if (isset($attribute[ProxyClosure::class])) {
            yield from $this->getProxyClosureAttribute($reflectionParameter);

            return;
        }

        if (isset($attribute[TaggedAs::class])) {
            yield from $this->getTaggedAsAttribute($reflectionParameter);

            return;
        }

        yield from $this->getInjectByCallableAttribute($reflectionParameter);
    }

    /**
     * @return \Generator<Inject>
     *
     * @throws AutowireExceptionInterface
     * @throws ContainerNeedSetExceptionInterface
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
                // PHPStan is not smart enough to parse such a condition.
                // @phpstan-ignore-next-line
                && null !== ($strType = $this->getParameterType($reflectionParameter))) {
                $inject = new Inject($strType);
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
     * @return \Generator<InjectByCallable>
     */
    private function getInjectByCallableAttribute(\ReflectionParameter $reflectionParameter): \Generator
    {
        $attributes = $reflectionParameter->getAttributes(InjectByCallable::class);

        if ([] === $attributes) {
            return;
        }

        $this->checkVariadic($reflectionParameter, \count($attributes), InjectByCallable::class);

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

    private function checkVariadic(\ReflectionParameter $reflectionParameter, int $countAttributes, string $attribute): void
    {
        if ($countAttributes > 1 && !$reflectionParameter->isVariadic()) {
            throw new AutowireAttributeException(
                \sprintf('The attribute #[%s] can only be applied once per non-variadic parameter.', $attribute)
            );
        }
    }
}
