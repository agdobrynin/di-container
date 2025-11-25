<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

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
use function sprintf;

final class AttributeReader
{
    public static function isAutowireExclude(ReflectionClass $class): bool
    {
        return !([] === $class->getAttributes(AutowireExclude::class));
    }

    public static function getDiFactoryAttribute(ReflectionClass $class): ?DiFactory
    {
        if (null === $attr = ($class->getAttributes(DiFactory::class)[0] ?? null)) {
            return null;
        }

        if ([] !== $class->getAttributes(Autowire::class)) {
            throw new AutowireAttributeException(
                sprintf('Cannot use together attributes #[%s] and #[%s] for class "%s".', DiFactory::class, Autowire::class, $class->name)
            );
        }

        /** @var DiFactory $attrFactory */
        $attrFactory = $attr->newInstance();
        $returnTypeDiFactoryInvoke = (string) (new ReflectionMethod($attrFactory->getIdentifier(), '__invoke'))->getReturnType();

        if (is_a($class->getName(), $returnTypeDiFactoryInvoke, true)) {
            return $attrFactory;
        }

        throw new AutowireParameterTypeException(
            sprintf('Definition factory %s::__invoke() must have return type hint as %s. Got return type: "%s"', $attrFactory->getIdentifier(), $class->getName(), $returnTypeDiFactoryInvoke)
        );
    }

    /**
     * @return Generator<Autowire>
     */
    public static function getAutowireAttribute(ReflectionClass $class): Generator
    {
        if ([] === ($attrs = $class->getAttributes(Autowire::class))) {
            return;
        }

        if ([] !== $class->getAttributes(DiFactory::class)) {
            throw new AutowireAttributeException(
                sprintf('Cannot use together attributes #[%s] and #[%s] for class "%s".', Autowire::class, DiFactory::class, $class->name)
            );
        }

        $containerIdentifier = '';

        foreach ($attrs as $attr) {
            /** @var Autowire $autowire */
            $autowire = $attr->newInstance();

            if ('' === $autowire->getIdentifier()) {
                $autowire = new Autowire($class->name, $autowire->isSingleton());
            }

            if ($containerIdentifier === $autowire->getIdentifier()) {
                throw new AutowireAttributeException(
                    sprintf('Container identifier "%s" already defined via previous php attribute #[%s("%s")] for class "%s".', $containerIdentifier, Autowire::class, $containerIdentifier, $class->name),
                );
            }

            $containerIdentifier = $autowire->getIdentifier();

            yield $autowire;
        }
    }

    public static function getServiceAttribute(ReflectionClass $class): ?Service
    {
        return ($class->getAttributes(Service::class)[0] ?? null)?->newInstance();
    }

    /**
     * @return Generator<Tag>
     */
    public static function getTagAttribute(ReflectionClass $class): Generator
    {
        if ([] === ($attrs = $class->getAttributes(Tag::class))) {
            return;
        }

        foreach ($attrs as $attr) {
            yield $attr->newInstance();
        }
    }

    /**
     * @return Generator<DiSetupAttributeInterface>
     */
    public static function getSetupAttribute(ReflectionClass $class): Generator
    {
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

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
     * @return Generator<Inject>
     *
     * @throws AutowireAttributeException|AutowireParameterTypeException
     */
    public static function getInjectAttribute(ReflectionParameter $param, ContainerInterface $container): Generator
    {
        if ([] === ($attrs = $param->getAttributes(Inject::class))) {
            return;
        }

        self::checkVariadic($param, count($attrs), Inject::class);

        foreach ($attrs as $attr) {
            /** @var Inject $inject */
            $inject = $attr->newInstance();

            if ('' === $inject->getIdentifier()) {
                $inject = new Inject(
                    Helper::getParameterTypeHint($param, $container)
                );
            }

            yield $inject;
        }
    }

    /**
     * @return Generator<ProxyClosure>
     */
    public static function getProxyClosureAttribute(ReflectionParameter $param): Generator
    {
        if ([] === ($attrs = $param->getAttributes(ProxyClosure::class))) {
            return;
        }

        self::checkVariadic($param, count($attrs), ProxyClosure::class);

        foreach ($attrs as $attr) {
            yield $attr->newInstance();
        }
    }

    /**
     * @return Generator<TaggedAs>
     */
    public static function getTaggedAsAttribute(ReflectionParameter $param): Generator
    {
        if ([] === ($attrs = $param->getAttributes(TaggedAs::class))) {
            return;
        }

        self::checkVariadic($param, count($attrs), TaggedAs::class);

        foreach ($attrs as $attr) {
            yield $attr->newInstance();
        }
    }

    /**
     * @return Generator<InjectByCallable>
     */
    public static function getInjectByCallableAttribute(ReflectionParameter $param): Generator
    {
        if ([] === ($attrs = $param->getAttributes(InjectByCallable::class))) {
            return;
        }

        self::checkVariadic($param, count($attrs), InjectByCallable::class);

        foreach ($attrs as $attr) {
            yield $attr->newInstance();
        }
    }

    /**
     * @return Generator<Inject>|Generator<InjectByCallable>|Generator<ProxyClosure>|Generator<TaggedAs>
     *
     * @throws AutowireAttributeException|AutowireParameterTypeException
     */
    public static function getAttributeOnParameter(ReflectionParameter $param, ContainerInterface $container): Generator
    {
        $groupAttrs = [];

        foreach ($param->getAttributes() as $attr) {
            $groupAttrs[$attr->getName()][] = $attr;
        }

        if ([] === $groupAttrs) {
            return;
        }

        // ⚠ attributes cannot be used together.
        $intersectAttrs = array_intersect(array_keys($groupAttrs), [Inject::class, ProxyClosure::class, TaggedAs::class, InjectByCallable::class]);

        if (count($intersectAttrs) > 1) {
            throw new AutowireAttributeException(
                sprintf('Only one of the attributes %s may be declared at %s in %s.', '#['.implode('], #[', $intersectAttrs).']', $param, Helper::functionName($param->getDeclaringFunction()))
            );
        }

        if (isset($groupAttrs[Inject::class])) {
            yield from AttributeReader::getInjectAttribute($param, $container);

            return;
        }

        if (isset($groupAttrs[ProxyClosure::class])) {
            yield from AttributeReader::getProxyClosureAttribute($param);

            return;
        }

        if (isset($groupAttrs[TaggedAs::class])) {
            yield from AttributeReader::getTaggedAsAttribute($param);

            return;
        }

        yield from AttributeReader::getInjectByCallableAttribute($param);
    }

    private static function checkVariadic(ReflectionParameter $param, int $countAttrs, string $attrClassName): void
    {
        if ($countAttrs > 1 && !$param->isVariadic()) {
            throw new AutowireAttributeException(
                sprintf('The attribute #[%s] can only be applied once per non-variadic %s in %s.', $attrClassName, $param, Helper::functionName($param->getDeclaringFunction()))
            );
        }
    }
}
