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
use function in_array;
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
        /** @var list<ReflectionAttribute<DiFactory>> $attrs */
        $attrs = $class->getAttributes(DiFactory::class);

        if ([] === $attrs) {
            return null;
        }

        if ([] !== $class->getAttributes(Autowire::class)) {
            throw new AutowireAttributeException(
                sprintf('Cannot use together attributes #[%s] and #[%s] for class "%s".', DiFactory::class, Autowire::class, $class->name)
            );
        }

        /** @var DiFactory $attrFactory */
        $attrFactory = $attrs[0]->newInstance();
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
        /** @var list<ReflectionAttribute<Autowire>> $attrs */
        $attrs = $class->getAttributes(Autowire::class);

        if ([] === $attrs) {
            return;
        }

        if ([] !== $class->getAttributes(DiFactory::class)) {
            throw new AutowireAttributeException(
                sprintf('Cannot use together attributes #[%s] and #[%s] for class "%s".', Autowire::class, DiFactory::class, $class->name)
            );
        }

        $containerIdentifier = '';

        /** @var ReflectionAttribute<Autowire> $attr */
        foreach ($attrs as $attr) {
            if ('' === ($autowire = $attr->newInstance())->getIdentifier()) {
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
        /** @var list<ReflectionAttribute<Service>> $attrs */
        $attrs = $class->getAttributes(Service::class);

        return [] === $attrs
            ? null
            : $attrs[0]->newInstance();
    }

    /**
     * @return Generator<Tag>
     */
    public static function getTagAttribute(ReflectionClass $class): Generator
    {
        foreach ($class->getAttributes(Tag::class) as $attr) {
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
            /** @var list<ReflectionAttribute<Setup|SetupImmutable>> $attrs */
            $attrs = [...$method->getAttributes(Setup::class), ...$method->getAttributes(SetupImmutable::class)];

            foreach ($attrs as $setupAttribute) {
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
    public static function getAttributeOnParameter(ReflectionParameter $param, ContainerInterface $container): Generator
    {
        $availableAttrs = [Inject::class, InjectByCallable::class, ProxyClosure::class, TaggedAs::class];

        /**
         * @var array<non-empty-string, non-empty-list<ReflectionAttribute>> $groupAttrs
         */
        $groupAttrs = [];

        foreach ($param->getAttributes() as $attr) {
            if (in_array($attr->getName(), $availableAttrs, true)) {
                if (!$param->isVariadic() && isset($groupAttrs[$attr->getName()])) {
                    throw new AutowireAttributeException(
                        sprintf('The php attribute %s::class can only be applied once per non-variadic %s in %s.', $attr->getName(), $param, Helper::functionName($param->getDeclaringFunction()))
                    );
                }

                $groupAttrs[$attr->getName()][] = $attr;
            }
        }

        if ([] === $groupAttrs) {
            return;
        }

        // ⚠ attributes cannot be used together.
        $intersectAttrs = array_intersect(array_keys($groupAttrs), $availableAttrs);

        if (count($intersectAttrs) > 1) {
            throw new AutowireAttributeException(
                sprintf('Only one of the php attributes %s::class may be declared at %s in %s.', implode('::class, ', $intersectAttrs), $param, Helper::functionName($param->getDeclaringFunction()))
            );
        }

        if (isset($groupAttrs[Inject::class])) {
            $paramTypeHint = null;

            /** @var ReflectionAttribute<Inject> $attr */
            foreach ($groupAttrs[Inject::class] as $attr) {
                if ('' === ($inject = $attr->newInstance())->getIdentifier()) {
                    $paramTypeHint ??= Helper::getParameterTypeHint($param, $container);
                    $inject = new Inject($paramTypeHint);
                }

                yield $inject;
            }

            return;
        }

        if (isset($groupAttrs[ProxyClosure::class])) {
            /** @var ReflectionAttribute<ProxyClosure> $attr */
            foreach ($groupAttrs[ProxyClosure::class] as $attr) {
                yield $attr->newInstance();
            }

            return;
        }

        if (isset($groupAttrs[TaggedAs::class])) {
            /** @var ReflectionAttribute<TaggedAs> $attr */
            foreach ($groupAttrs[TaggedAs::class] as $attr) {
                yield $attr->newInstance();
            }

            return;
        }

        /** @var ReflectionAttribute<InjectByCallable> $attr */
        foreach ($groupAttrs[InjectByCallable::class] as $attr) {
            yield $attr->newInstance();
        }
    }
}
