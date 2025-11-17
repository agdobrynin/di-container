<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Generator;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\AutowireExclude;
use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Exception\AutowireParameterTypeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiSetupAttributeInterface;
use Psr\Container\ContainerInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

use function array_intersect;
use function array_keys;
use function count;
use function implode;
use function is_a;
use function Kaspi\DiContainer\functionName;
use function sprintf;

trait AttributeReaderTrait
{
    use ParameterTypeByReflectionTrait;

    public function isAutowireExclude(ReflectionClass $reflectionClass): bool
    {
        return !([] === $reflectionClass->getAttributes(AutowireExclude::class));
    }

    private function getDiFactoryAttribute(ReflectionClass $reflectionClass): ?DiFactory
    {
        if (null === $attribute = ($reflectionClass->getAttributes(DiFactory::class)[0] ?? null)) {
            return null;
        }

        if ([] !== $reflectionClass->getAttributes(Autowire::class)) {
            throw new AutowireAttributeException(
                sprintf('Cannot use together attributes #[%s] and #[%s] for class "%s".', DiFactory::class, Autowire::class, $reflectionClass->name)
            );
        }

        /** @var DiFactory $attr */
        $attr = $attribute->newInstance();
        $returnTypeDiFactoryInvoke = (string) (new ReflectionMethod($attr->getIdentifier(), '__invoke'))->getReturnType();

        if (is_a($reflectionClass->getName(), $returnTypeDiFactoryInvoke, true)) {
            return $attr;
        }

        throw new AutowireParameterTypeException(
            sprintf('Definition factory %s::__invoke() must have return type hint as %s. Got return type: "%s"', $attr->getIdentifier(), $reflectionClass->getName(), $returnTypeDiFactoryInvoke)
        );
    }

    /**
     * @return Generator<Autowire>
     */
    private function getAutowireAttribute(ReflectionClass $reflectionClass): Generator
    {
        $attributes = $reflectionClass->getAttributes(Autowire::class);

        if ([] === $attributes) {
            return;
        }

        if ([] !== $reflectionClass->getAttributes(DiFactory::class)) {
            throw new AutowireAttributeException(
                sprintf('Cannot use together attributes #[%s] and #[%s] for class "%s".', Autowire::class, DiFactory::class, $reflectionClass->name)
            );
        }

        $containerIdentifier = '';

        foreach ($attributes as $attribute) {
            /** @var Autowire $autowire */
            $autowire = $attribute->newInstance();

            if ('' === $autowire->getIdentifier()) {
                $autowire = new Autowire($reflectionClass->name, $autowire->isSingleton());
            }

            if ($containerIdentifier === $autowire->getIdentifier()) {
                throw new AutowireAttributeException(
                    sprintf('Container identifier "%s" already defined by #[%s] for class "%s".', $autowire->getIdentifier(), Autowire::class, $reflectionClass->name),
                );
            }

            $containerIdentifier = $autowire->getIdentifier();

            yield $autowire;
        }
    }

    private function getServiceAttribute(ReflectionClass $reflectionClass): ?Service
    {
        return ($reflectionClass->getAttributes(Service::class)[0] ?? null)?->newInstance();
    }

    /**
     * @return Generator<Tag>
     */
    private function getTagAttribute(ReflectionClass $reflectionClass): Generator
    {
        $attributes = $reflectionClass->getAttributes(Tag::class);

        if ([] === $attributes) {
            return;
        }

        foreach ($attributes as $attribute) {
            yield $attribute->newInstance();
        }
    }

    /**
     * @return Generator<DiSetupAttributeInterface>
     */
    private function getSetupAttribute(ReflectionClass $reflectionClass): Generator
    {
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            /** @var ReflectionAttribute[] $attrs */
            $attrs = [...$method->getAttributes(Setup::class), ...$method->getAttributes(SetupImmutable::class)];

            foreach ($attrs as $setupAttribute) {
                /** @var DiSetupAttributeInterface $setup */
                $setup = $setupAttribute->newInstance();
                $setup->setMethod($method->getName());

                yield $setup;
            }
        }
    }

    /**
     * @return Generator<Inject>|Generator<InjectByCallable>|Generator<ProxyClosure>|Generator<TaggedAs>
     *
     * @throws AutowireAttributeException|AutowireParameterTypeException
     */
    private function getAttributeOnParameter(ReflectionParameter $reflectionParameter, ContainerInterface $container): Generator
    {
        $groupAttributes = [];

        foreach ($reflectionParameter->getAttributes() as $attribute) {
            $groupAttributes[$attribute->getName()][] = $attribute;
        }

        if ([] === $groupAttributes) {
            return;
        }

        // ⚠ attributes cannot be used together.
        $intersectAttrs = array_intersect(array_keys($groupAttributes), [Inject::class, ProxyClosure::class, TaggedAs::class, InjectByCallable::class]);

        if (count($intersectAttrs) > 1) {
            throw new AutowireAttributeException(
                sprintf('Only one of the attributes %s may be declared.', '#['.implode('], #[', $intersectAttrs).']')
            );
        }

        if (isset($groupAttributes[Inject::class])) {
            yield from $this->getInjectAttribute($reflectionParameter, $container);

            return;
        }

        if (isset($groupAttributes[ProxyClosure::class])) {
            yield from $this->getProxyClosureAttribute($reflectionParameter);

            return;
        }

        if (isset($groupAttributes[TaggedAs::class])) {
            yield from $this->getTaggedAsAttribute($reflectionParameter);

            return;
        }

        yield from $this->getInjectByCallableAttribute($reflectionParameter);
    }

    /**
     * @return Generator<Inject>
     *
     * @throws AutowireAttributeException|AutowireParameterTypeException
     */
    private function getInjectAttribute(ReflectionParameter $reflectionParameter, ContainerInterface $container): Generator
    {
        $attributes = $reflectionParameter->getAttributes(Inject::class);

        if ([] === $attributes) {
            return;
        }

        $this->checkVariadic($reflectionParameter, count($attributes), Inject::class);

        foreach ($attributes as $attribute) {
            /** @var Inject $inject */
            $inject = $attribute->newInstance();

            if ('' === $inject->getIdentifier()) {
                $inject = new Inject(
                    $this->getParameterType($reflectionParameter, $container)
                );
            }

            yield $inject;
        }
    }

    /**
     * @return Generator<ProxyClosure>
     */
    private function getProxyClosureAttribute(ReflectionParameter $reflectionParameter): Generator
    {
        $attributes = $reflectionParameter->getAttributes(ProxyClosure::class);

        if ([] === $attributes) {
            return;
        }

        $this->checkVariadic($reflectionParameter, count($attributes), ProxyClosure::class);

        foreach ($attributes as $attribute) {
            yield $attribute->newInstance();
        }
    }

    /**
     * @return Generator<TaggedAs>
     */
    private function getTaggedAsAttribute(ReflectionParameter $reflectionParameter): Generator
    {
        $attributes = $reflectionParameter->getAttributes(TaggedAs::class);

        if ([] === $attributes) {
            return;
        }

        $this->checkVariadic($reflectionParameter, count($attributes), TaggedAs::class);

        foreach ($attributes as $attribute) {
            yield $attribute->newInstance();
        }
    }

    /**
     * @return Generator<InjectByCallable>
     */
    private function getInjectByCallableAttribute(ReflectionParameter $reflectionParameter): Generator
    {
        $attributes = $reflectionParameter->getAttributes(InjectByCallable::class);

        if ([] === $attributes) {
            return;
        }

        $this->checkVariadic($reflectionParameter, count($attributes), InjectByCallable::class);

        foreach ($attributes as $attribute) {
            yield $attribute->newInstance();
        }
    }

    private function checkVariadic(ReflectionParameter $param, int $countAttributes, string $attribute): void
    {
        if ($countAttributes > 1 && !$param->isVariadic()) {
            throw new AutowireAttributeException(
                sprintf('The attribute #[%s] can only be applied once per non-variadic %s in %s.', $attribute, $param, functionName($param->getDeclaringFunction()))
            );
        }
    }
}
