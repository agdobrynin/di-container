<?php

declare(strict_types=1);

namespace Tests\AttributeReader\DiFactory;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;
use Tests\AttributeReader\DiFactory\Fixtures\ClassWithAttrsDiFactoryAndAutowire;
use Tests\AttributeReader\DiFactory\Fixtures\FooFactoryOne;
use Tests\AttributeReader\DiFactory\Fixtures\FooFactoryTwo;
use Tests\AttributeReader\DiFactory\Fixtures\FooFactoryWithArgs;
use Tests\AttributeReader\DiFactory\Fixtures\FooFail;
use Tests\AttributeReader\DiFactory\Fixtures\IntFactory;
use Tests\AttributeReader\DiFactory\Fixtures\Main;
use Tests\AttributeReader\DiFactory\Fixtures\MainFirstDiFactory;
use Tests\AttributeReader\DiFactory\Fixtures\NoDiFactories;
use Tests\AttributeReader\DiFactory\Fixtures\StrFactory;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiFactory::class)]
#[CoversClass(Helper::class)]
#[CoversClass(DiDefinitionGet::class)]
class DiFactoryReaderTest extends TestCase
{
    public function testHasOneAttribute(): void
    {
        $attribute = AttributeReader::getDiFactoryAttributeOnClass(new ReflectionClass(Main::class));

        $this->assertInstanceOf(DiFactory::class, $attribute);
        $this->assertEquals(MainFirstDiFactory::class, $attribute->definition);
    }

    public function testNoneAttribute(): void
    {
        $attribute = AttributeReader::getDiFactoryAttributeOnClass(new ReflectionClass(NoDiFactories::class));

        $this->assertNull($attribute);
    }

    public function testCannotUseTogetherDiFactoryAndAutowire(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Only one of the php attributes.+DiFactory::class.+Autowire::class/');

        AttributeReader::getDiFactoryAttributeOnClass(new ReflectionClass(ClassWithAttrsDiFactoryAndAutowire::class));
    }

    public function testManyFactoryOnClass(): void
    {
        $this->expectException(AutowireAttributeException::class);
        $this->expectExceptionMessage('can be applied once for');

        AttributeReader::getDiFactoryAttributeOnClass(new ReflectionClass(FooFail::class));
    }

    public function testFailManyAttributeNonVariadicParam(): void
    {
        $this->expectException(AutowireAttributeException::class);
        $this->expectExceptionMessage('can be applied once per non-variadic Parameter #0');

        $f = static fn (
            #[DiFactory(FooFactoryOne::class)]
            #[DiFactory(FooFactoryTwo::class)]
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        AttributeReader::getAttributeOnParameter(
            $p,
            $this->createMock(ContainerInterface::class)
        )->valid();
    }

    public function testVariadicParam(): void
    {
        $f = static fn (
            #[DiFactory(StrFactory::class)]
            #[DiFactory(IntFactory::class)]
            mixed ...$a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $res = AttributeReader::getAttributeOnParameter($p, $this->createMock(ContainerInterface::class));

        self::assertTrue($res->valid());

        self::assertInstanceOf(DiFactory::class, $res->current());
        self::assertEquals(StrFactory::class, $res->current()->definition);

        $res->next();

        self::assertInstanceOf(DiFactory::class, $res->current());
        self::assertEquals(IntFactory::class, $res->current()->definition);

        $res->next();

        self::assertFalse($res->valid());
    }

    public function testFactoryOnParamWithArgs(): void
    {
        $f = static fn (
            #[DiFactory(
                [FooFactoryWithArgs::class, 'create'],
                arguments: [
                    'apiKey' => new DiDefinitionGet('keys.api_key'),
                ]
            )]
            mixed $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $res = AttributeReader::getAttributeOnParameter($p, $this->createMock(ContainerInterface::class));

        $factory = $res->current();

        self::assertInstanceOf(DiFactory::class, $factory);
        self::assertEquals([
            'apiKey' => new DiDefinitionGet('keys.api_key'),
        ], $factory->arguments);
    }
}
