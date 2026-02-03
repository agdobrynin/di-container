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
use TypeError;

use function array_filter;
use function array_intersect;
use function array_keys;
use function implode;
use function in_array;
use function sprintf;

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
        $groupAttrs = self::getNotIntersectGroupAttrs($class->getAttributes(), $class);

        /** @var null|list<ReflectionAttribute<DiFactory>> $groupDiFactory */
        $groupDiFactory = $groupAttrs[DiFactory::class] ?? null;

        if (null === $groupDiFactory) {
            return null;
        }

        if (isset($groupDiFactory[1])) {
            throw new AutowireAttributeException(
                sprintf('The attribute %s::class can be applied once for %s class.', DiFactory::class, $class->name)
            );
        }

        return $groupDiFactory[0]->newInstance();
    }

    /**
     * @return Generator<Autowire>
     *
     * @throws AutowireAttributeException
     */
    public static function getAutowireAttribute(ReflectionClass $class): Generator
    {
        $groupAttrs = self::getNotIntersectGroupAttrs($class->getAttributes(), $class);

        if (!isset($groupAttrs[Autowire::class])) {
            return;
        }

        $containerIdentifier = '';

        /** @var ReflectionAttribute<Autowire> $attr */
        foreach ($groupAttrs[Autowire::class] as $attr) {
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
     * @return Generator<DiFactory|Inject|InjectByCallable|ProxyClosure|TaggedAs>
     *
     * @throws AutowireAttributeException|AutowireParameterTypeException
     */
    public static function getAttributeOnParameter(ReflectionParameter $param, ContainerInterface $container): Generator
    {
        $supportAttrs = [DiFactory::class, Inject::class, InjectByCallable::class, ProxyClosure::class, TaggedAs::class];

        $attrs = array_filter($param->getAttributes(), static fn (ReflectionAttribute $attr) => in_array($attr->getName(), $supportAttrs, true));

        if ([] === $attrs) {
            return;
        }

        if (!$param->isVariadic() && isset($attrs[1])) {
            throw new AutowireAttributeException(
                sprintf('The php attribute can be applied once per non-variadic %s in %s.', $param, Helper::functionName($param->getDeclaringFunction()))
            );
        }

        foreach ($attrs as $attr) {
            if (Inject::class === $attr->getName()) {
                /** @var ReflectionAttribute<Inject> $attr */
                $attrInit = $attr->newInstance();

                if ('' === $attrInit->getIdentifier()) {
                    $paramType ??= Helper::getParameterTypeHint($param, $container);
                    $attrInit = new Inject($paramType);
                }
            } elseif (InjectByCallable::class === $attr->getName()) {
                try {
                    /** @var ReflectionAttribute<InjectByCallable> $attr */
                    $attrInit = $attr->newInstance();
                } catch (TypeError $e) {
                    throw new AutowireAttributeException(
                        message: sprintf('Unable to create an instance of PHP attribute "%s". Parameter $callable must be of type callable.', InjectByCallable::class),
                        previous: $e
                    );
                }
            } else {
                /** @var ReflectionAttribute<DiFactory|ProxyClosure|TaggedAs> $attr */
                $attrInit = $attr->newInstance();
            }

            yield $attrInit;
        }
    }

    /**
     * @param list<ReflectionAttribute> $attrs
     *
     * @return array<class-string, list<ReflectionAttribute<Autowire|DiFactory>>>
     *
     * @throws AutowireAttributeException
     */
    private static function getNotIntersectGroupAttrs(array $attrs, ReflectionClass $whereUseAttribute): array
    {
        $availableAttrs = [Autowire::class, DiFactory::class];
        $groupAttrs = [];

        foreach ($attrs as $attr) {
            if (in_array($attr->getName(), $availableAttrs, true)) {
                $groupAttrs[$attr->getName()][] = $attr;
            }
        }

        if ([] === $groupAttrs) {
            return [];
        }

        $intersectAttrs = array_intersect(array_keys($groupAttrs), $availableAttrs);

        if (isset($intersectAttrs[1])) {
            $strIntersect = implode('::class, ', $intersectAttrs).'::class';

            throw new AutowireAttributeException(
                sprintf('Only one of the php attributes %s may be declared at %s::class.', $strIntersect, $whereUseAttribute->name)
            );
        }

        return $groupAttrs;
    }
}
