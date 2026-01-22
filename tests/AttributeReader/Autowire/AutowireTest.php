<?php

declare(strict_types=1);

namespace Tests\AttributeReader\Autowire;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\AttributeReader\Autowire\Fixtures\ClassWithDiFactoryAndAutowire;
use Tests\AttributeReader\Autowire\Fixtures\MultipleAutowire;
use Tests\AttributeReader\Autowire\Fixtures\MultipleAutowireFail;

use function iterator_to_array;

/**
 * @internal
 */
#[CoversClass(Autowire::class)]
#[CoversClass(AttributeReader::class)]
class AutowireTest extends TestCase
{
    public function testAutowireCannotUseWithDiFactoryAndAutowire(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Only one of the php attributes.+DiFactory::class.+Autowire::class/');

        AttributeReader::getAutowireAttribute(new ReflectionClass(ClassWithDiFactoryAndAutowire::class))->valid();
    }

    public function testMultipleAutowireSuccess(): void
    {
        $attrs = AttributeReader::getAutowireAttribute(new ReflectionClass(MultipleAutowire::class));

        $this->assertTrue($attrs->valid());

        $this->assertEquals(MultipleAutowire::class, $attrs->current()->getIdentifier());
        $this->assertNull($attrs->current()->isSingleton());

        $attrs->next();

        $this->assertEquals('service.singleton', $attrs->current()->getIdentifier());
        $this->assertTrue($attrs->current()->isSingleton());

        $attrs->next();

        $this->assertEquals('service.none_singleton', $attrs->current()->getIdentifier());
        $this->assertFalse($attrs->current()->isSingleton());

        $attrs->next();

        $this->assertFalse($attrs->valid());
    }

    public function testAutowireContainerIdentifierNoneUnique(): void
    {
        $attrs = AttributeReader::getAutowireAttribute(new ReflectionClass(MultipleAutowireFail::class));

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Container identifier "service" already defined/');

        iterator_to_array($attrs);
    }
}
