<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\DiFactory;

use Generator;
use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Exception\AutowireParameterTypeException;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Traits\AttributeReader\DiFactory\Fixtures\ClassWithAttrsDiFactoryAndAutowire;
use Tests\Traits\AttributeReader\DiFactory\Fixtures\Main;
use Tests\Traits\AttributeReader\DiFactory\Fixtures\MainFail;
use Tests\Traits\AttributeReader\DiFactory\Fixtures\MainFailTwo;
use Tests\Traits\AttributeReader\DiFactory\Fixtures\MainFirstDiFactory;
use Tests\Traits\AttributeReader\DiFactory\Fixtures\NoDiFactories;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 *
 * @internal
 */
class DiFactoryReaderTest extends TestCase
{
    protected $reader;

    public function setUp(): void
    {
        $this->reader = new class {
            use AttributeReaderTrait {
                getDiFactoryAttribute as public;
            }
        };
    }

    public function tearDown(): void
    {
        $this->reader = null;
    }

    public function testHasOneAttribute(): void
    {
        $attribute = $this->reader->getDiFactoryAttribute(new ReflectionClass(Main::class));

        $this->assertInstanceOf(DiFactory::class, $attribute);
        $this->assertEquals(MainFirstDiFactory::class, $attribute->getIdentifier());
    }

    public function testNoneAttribute(): void
    {
        $attribute = $this->reader->getDiFactoryAttribute(new ReflectionClass(NoDiFactories::class));

        $this->assertNull($attribute);
    }

    public function testCannotUseTogetherDiFactoryAndAutowire(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot use together attributes.+DiFactory.+Autowire\]/');

        $this->reader->getDiFactoryAttribute(new ReflectionClass(ClassWithAttrsDiFactoryAndAutowire::class));
    }

    /**
     * @dataProvider dataProviderReturnTypeFromFactory
     */
    public function testReturnTypeFromFactory(string $class): void
    {
        $this->expectException(AutowireParameterTypeException::class);

        $this->reader->getDiFactoryAttribute(new ReflectionClass($class));
    }

    public function dataProviderReturnTypeFromFactory(): Generator
    {
        yield 'For class '.MainFail::class => [MainFail::class];

        yield 'For class '.MainFailTwo::class => [MainFailTwo::class];
    }
}
