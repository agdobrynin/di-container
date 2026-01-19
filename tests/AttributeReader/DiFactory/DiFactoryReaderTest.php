<?php

declare(strict_types=1);

namespace Tests\AttributeReader\DiFactory;

use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Exception\AutowireParameterTypeException;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\AttributeReader\DiFactory\Fixtures\ClassWithAttrsDiFactoryAndAutowire;
use Tests\AttributeReader\DiFactory\Fixtures\Main;
use Tests\AttributeReader\DiFactory\Fixtures\MainFail;
use Tests\AttributeReader\DiFactory\Fixtures\MainFailTwo;
use Tests\AttributeReader\DiFactory\Fixtures\MainFirstDiFactory;
use Tests\AttributeReader\DiFactory\Fixtures\NoDiFactories;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiFactory::class)]
class DiFactoryReaderTest extends TestCase
{
    public function testHasOneAttribute(): void
    {
        $attribute = AttributeReader::getDiFactoryAttributeOnClass(new ReflectionClass(Main::class));

        $this->assertInstanceOf(DiFactory::class, $attribute);
        $this->assertEquals(MainFirstDiFactory::class, $attribute->getIdentifier());
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

    #[DataProvider('dataProviderReturnTypeFromFactory')]
    public function testReturnTypeFromFactory(string $class): void
    {
        $this->expectException(AutowireParameterTypeException::class);

        AttributeReader::getDiFactoryAttributeOnClass(new ReflectionClass($class));
    }

    public static function dataProviderReturnTypeFromFactory(): Generator
    {
        yield 'For class '.MainFail::class => [MainFail::class];

        yield 'For class '.MainFailTwo::class => [MainFailTwo::class];
    }
}
