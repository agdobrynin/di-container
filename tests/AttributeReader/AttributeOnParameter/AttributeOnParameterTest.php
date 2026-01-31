<?php

declare(strict_types=1);

namespace Tests\AttributeReader\AttributeOnParameter;

use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionParameter;
use Tests\AttributeReader\AttributeOnParameter\Fixtures\Foo;
use Tests\AttributeReader\AttributeOnParameter\Fixtures\FooAttr;
use Tests\AttributeReader\AttributeOnParameter\Fixtures\FooFactory;

/**
 * @internal
 */
#[CoversClass(Helper::class)]
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiFactory::class)]
#[CoversClass(Inject::class)]
#[CoversClass(InjectByCallable::class)]
#[CoversClass(ProxyClosure::class)]
#[CoversClass(TaggedAs::class)]
class AttributeOnParameterTest extends TestCase
{
    #[DataProvider('dataProviderParam')]
    public function testAttributeOnParameterIntersect(ReflectionParameter $param): void
    {
        $this->expectException(AutowireAttributeException::class);
        $this->expectExceptionMessage('The php attribute can be applied once per non-variadic Parameter');

        AttributeReader::getAttributeOnParameter(
            $param,
            $this->createMock(DiContainerInterface::class)
        )->valid();
    }

    public static function dataProviderParam(): Generator
    {
        yield 'Inject and ProxyClosure' => [
            (new ReflectionFunction(static fn (#[Inject, ProxyClosure('service.one')] $param) => true))->getParameters()[0],
        ];

        yield 'ProxyClosure and TaggedAs' => [
            (new ReflectionFunction(static fn (#[ProxyClosure('service.one'), TaggedAs('tags.one')] $param) => true))->getParameters()[0],
        ];

        yield 'InjectByCallable and TaggedAs' => [
            (new ReflectionFunction(static fn (#[TaggedAs('tags.one'), InjectByCallable('func2')] $param) => true))->getParameters()[0],
        ];

        yield 'Inject and DiFactory' => [
            (new ReflectionFunction(static fn (#[Inject('service.one'), DiFactory(FooFactory::class)] $param) => true))->getParameters()[0],
        ];
    }

    public function testMixedAttributes(): void
    {
        $f = static fn (
            #[DiFactory(FooFactory::class)]
            #[FooAttr(Foo::class)]
            mixed ...$a
        ) => '';
        $param = new ReflectionParameter($f, 0);

        $res = [...AttributeReader::getAttributeOnParameter($param, $this->createMock(DiContainerInterface::class))];

        self::assertCount(1, $res);
    }

    public function testAttributesOnVariadic(): void
    {
        $f = static fn (
            #[DiFactory(FooFactory::class)]
            #[Inject('service.one')]
            #[InjectByCallable('\uniqid')]
            #[ProxyClosure('service.heavy')]
            #[TaggedAs('tags.one')]
            mixed ...$a
        ) => '';
        $param = new ReflectionParameter($f, 0);

        $res = [...AttributeReader::getAttributeOnParameter($param, $this->createMock(DiContainerInterface::class))];

        self::assertCount(5, $res);

        self::assertEquals(FooFactory::class, $res[0]->getDefinition());
        self::assertNull($res[0]->isSingleton());

        self::assertEquals('service.one', $res[1]->getIdentifier());

        self::assertEquals('\uniqid', $res[2]->getCallable());
        self::assertIsCallable($res[2]->getCallable());

        self::assertEquals('service.heavy', $res[3]->getIdentifier());

        self::assertEquals('tags.one', $res[4]->getIdentifier());
        self::assertTrue($res[4]->isLazy());
    }
}
