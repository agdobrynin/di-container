<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\Autowire;

use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use PHPUnit\Framework\TestCase;
use Tests\Traits\AttributeReader\Autowire\Fixtures\ClassWithAttrsAutowireExcludeAndAutowire;
use Tests\Traits\AttributeReader\Autowire\Fixtures\ClassWithDiFactoryAndAutowire;
use Tests\Traits\AttributeReader\Autowire\Fixtures\MultipleAutowire;
use Tests\Traits\AttributeReader\Autowire\Fixtures\MultipleAutowireFail;

/**
 * @covers \Kaspi\DiContainer\Attributes\Autowire
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 *
 * @internal
 */
class AutowireTest extends TestCase
{
    use AttributeReaderTrait;

    public function testAutowireCannotUseWithAutowireExcludeAndAutowire(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot use together attributes.+Autowire.+AutowireExclude/');

        $this->getAutowireAttribute(new \ReflectionClass(ClassWithAttrsAutowireExcludeAndAutowire::class))->valid();
    }

    public function testAutowireCannotUseWithDiFactoryAndAutowire(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot use together attributes.+DiFactory.+Autowire/');

        $this->getAutowireAttribute(new \ReflectionClass(ClassWithDiFactoryAndAutowire::class))->valid();
    }

    public function testMultipleAutowireSuccess(): void
    {
        $attrs = $this->getAutowireAttribute(new \ReflectionClass(MultipleAutowire::class));

        $this->assertTrue($attrs->valid());

        $this->assertEquals(MultipleAutowire::class, $attrs->current()->getIdentifier());
        $this->assertFalse($attrs->current()->isSingleton());

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
        $attrs = $this->getAutowireAttribute(new \ReflectionClass(MultipleAutowireFail::class));

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Container identifier "service" already defined/');

        \iterator_to_array($attrs);
    }
}
