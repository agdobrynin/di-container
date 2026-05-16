<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Generator;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\AutowireExclude;
use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\DiRuntime;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\Attributes\Parameter;
use Kaspi\DiContainer\Attributes\ParameterRuntime;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\Attributes\SetupPriority;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Exception\AutowireParameterTypeException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use TypeError;

use function array_filter;
use function sprintf;
use function usort;

final class AttributeReader
{
    public static function isAutowireExclude(ReflectionClass $class): bool
    {
        return !([] === $class->getAttributes(AutowireExclude::class));
    }

    /**
     * @throws AutowireAttributeException|AutowireParameterTypeException
     */
    public static function getDiFactoryAttributeOnClass(ReflectionClass $class): ?DiFactory
    {
        $factoryAttrs = self::getNotIntersectAttributes($class, DiFactory::class, false, [Autowire::class, DiRuntime::class]);

        return isset($factoryAttrs[0])
            ? $factoryAttrs[0]->newInstance()
            : null;
    }

    /**
     * @return Generator<DiRuntime>
     *
     * @throws AutowireAttributeException
     */
    public static function getDiRuntimeAttribute(ReflectionClass $class): Generator
    {
        $diRuntimeAttrs = self::getNotIntersectAttributes($class, DiRuntime::class, true, [DiFactory::class, Autowire::class]);

        $previousContainerIdentifier = '';

        /** @var ReflectionAttribute<DiRuntime> $attr */
        foreach ($diRuntimeAttrs as $attr) {
            $diRuntime = $attr->newInstance();
            $currentContainerIdentifier = '' !== $diRuntime->containerIdentifier
                ? $diRuntime->containerIdentifier
                : $class->name;

            if ($previousContainerIdentifier === $currentContainerIdentifier) {
                throw new AutowireAttributeException(
                    sprintf('Container identifier "%s" already defined via previous php attribute #[%s("%s")] for class "%s".', $previousContainerIdentifier, DiRuntime::class, $previousContainerIdentifier, $class->name),
                );
            }

            $previousContainerIdentifier = $currentContainerIdentifier;

            yield $diRuntime;
        }
    }

    /**
     * @return Generator<Autowire>
     *
     * @throws AutowireAttributeException
     */
    public static function getAutowireAttribute(ReflectionClass $class): Generator
    {
        $autowireAttrs = self::getNotIntersectAttributes($class, Autowire::class, true, [DiRuntime::class, DiFactory::class]);

        if ([] === $autowireAttrs) {
            return;
        }

        $containerIdentifier = '';

        /** @var ReflectionAttribute<Autowire> $attr */
        foreach ($autowireAttrs as $attr) {
            if ('' === ($autowire = $attr->newInstance())->id) {
                $autowire = new Autowire($class->name, $autowire->isSingleton, $autowire->arguments);
            }

            if ($containerIdentifier === $autowire->id) {
                throw new AutowireAttributeException(
                    sprintf('Container identifier "%s" already defined via previous php attribute #[%s("%s")] for class "%s".', $containerIdentifier, Autowire::class, $containerIdentifier, $class->name),
                );
            }

            $containerIdentifier = $autowire->id;

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
     * @return Generator<int, Setup|SetupImmutable>
     */
    public static function getSetupAttribute(ReflectionClass $class): Generator
    {
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

        /**
         * @var list<array{0: int, 1: list<Setup|SetupImmutable>}> $setups
         */
        $setups = [];

        foreach ($methods as $method) {
            /** @var list<ReflectionAttribute<Setup|SetupImmutable>> $attrs */
            $attrs = [...$method->getAttributes(Setup::class), ...$method->getAttributes(SetupImmutable::class)];

            /** @var list<Setup|SetupImmutable> $methodSetups */
            $methodSetups = [];

            foreach ($attrs as $setupAttribute) {
                $setupMethod = $setupAttribute->newInstance();
                $setupMethod->setMethod($method->getName());
                $methodSetups[] = $setupMethod;
            }

            $priorityAttr = $method->getAttributes(SetupPriority::class)[0] ?? null;

            $priority = null !== $priorityAttr
                ? ($priorityAttr->newInstance())->priority
                : null;

            $setups[] = [$priority, $methodSetups];
        }

        usort($setups, static function (array $a, array $b) {
            [$priorityA] = $a;
            [$priorityB] = $b;

            return $priorityB <=> $priorityA;
        });

        /** @var list<Setup|SetupImmutable> $methodSetups */
        foreach ($setups as [, $methodSetups]) {
            yield from $methodSetups;
        }
    }

    /**
     * @return Generator<DiFactory|Inject|InjectByCallable|Parameter|ParameterRuntime|ProxyClosure|TaggedAs>
     *
     * @throws AutowireAttributeException
     */
    public static function getAttributeOnParameter(ReflectionParameter $param): Generator
    {
        $flipSupportAttrs = [
            DiFactory::class => true,
            Inject::class => true,
            InjectByCallable::class => true,
            ProxyClosure::class => true,
            TaggedAs::class => true,
            Parameter::class => true,
            ParameterRuntime::class => true,
        ];

        $attrs = array_filter($param->getAttributes(), static fn (ReflectionAttribute $attr) => isset($flipSupportAttrs[$attr->getName()]));

        if ([] === $attrs) {
            return;
        }

        if (!$param->isVariadic() && isset($attrs[1])) {
            throw new AutowireAttributeException(
                sprintf('The php attribute can be applied once per non-variadic %s in %s.', $param, Helper::functionName($param->getDeclaringFunction()))
            );
        }

        /** @var null|string $paramType */
        $paramType = null;

        /**
         * @var ReflectionAttribute<DiFactory|Inject|InjectByCallable|Parameter|ParameterRuntime|ProxyClosure|TaggedAs> $attr
         */
        foreach ($attrs as $attr) {
            try {
                yield $attr->newInstance();
            } catch (TypeError $e) {
                throw new AutowireAttributeException(
                    message: sprintf('Unable to create an instance of PHP attribute "%s". Reason by: %s', $attr->getName(), $e->getMessage()),
                    previous: $e
                );
            }
        }
    }

    /**
     * @template T of Autowire|DiFactory|DiRuntime
     *
     * @param class-string<T>    $mainAttribute
     * @param list<class-string> $notIntersectAttrs
     *
     * @return list<ReflectionAttribute<T>>
     */
    private static function getNotIntersectAttributes(ReflectionClass $class, string $mainAttribute, bool $isRepeatedMainAttribute, array $notIntersectAttrs): array
    {
        $mainAttrs = $class->getAttributes($mainAttribute);

        if ([] === $mainAttrs) {
            return [];
        }

        if (!$isRepeatedMainAttribute && isset($mainAttrs[1])) {
            throw new AutowireAttributeException(
                sprintf('The attribute %s can be applied once for %s class.', $mainAttribute, $class->name)
            );
        }

        foreach ($notIntersectAttrs as $attr) {
            if ([] !== $class->getAttributes($attr)) {
                throw new AutowireAttributeException(
                    sprintf('The attributes %s and %s cannot be declared together at class %s.', $mainAttribute, $attr, $class->name)
                );
            }
        }

        return $mainAttrs;
    }
}
