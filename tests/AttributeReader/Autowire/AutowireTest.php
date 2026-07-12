<?php

declare(strict_types=1);

namespace Tests\AttributeReader\Autowire;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\AttributeReader\Autowire\Fixtures\Bar;
use Tests\AttributeReader\Autowire\Fixtures\BarFailResetter;
use Tests\AttributeReader\Autowire\Fixtures\Baz;
use Tests\AttributeReader\Autowire\Fixtures\BazResetter;
use Tests\AttributeReader\Autowire\Fixtures\ClassWithDiFactoryAndAutowire;
use Tests\AttributeReader\Autowire\Fixtures\Foo;
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
        $this->expectExceptionMessageMatches('/The attributes .+Autowire and .+DiFactory cannot be declared together/');

        AttributeReader::getAutowireAttribute(new ReflectionClass(ClassWithDiFactoryAndAutowire::class))->valid();
    }

    public function testMultipleAutowireSuccess(): void
    {
        $attrs = AttributeReader::getAutowireAttribute(new ReflectionClass(MultipleAutowire::class));

        $this->assertTrue($attrs->valid());

        $this->assertEquals('', $attrs->current()->id);
        $this->assertNull($attrs->current()->isSingleton);
        $this->assertEquals(['foo'], $attrs->current()->arguments);
        $this->assertFalse($attrs->current()->getResetter());

        $attrs->next();

        $this->assertEquals('service.singleton', $attrs->current()->id);
        $this->assertTrue($attrs->current()->isSingleton);
        $this->assertEquals(['bar'], $attrs->current()->arguments);
        $this->assertFalse($attrs->current()->getResetter());

        $attrs->next();

        $this->assertEquals('service.none_singleton', $attrs->current()->id);
        $this->assertFalse($attrs->current()->isSingleton);
        $this->assertEquals(['baz'], $attrs->current()->arguments);
        $this->assertFalse($attrs->current()->getResetter());

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

    public function testAutowireAndDiRuntime(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/The attributes .+Autowire and .+DiRuntime cannot be declared together/');

        [...AttributeReader::getAutowireAttribute(new ReflectionClass(Foo::class))];
    }

    public function testAutowireWithFailResetter(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Argument.+\(\$resetter\) must be of type callable\|string\|false, array given/');

        [...AttributeReader::getAutowireAttribute(new ReflectionClass(BarFailResetter::class))];
    }

    public function testAutowireResetterAsMethod(): void
    {
        $autowire = AttributeReader::getAutowireAttribute(new ReflectionClass(Bar::class))->current();

        self::assertEquals('doReset', $autowire->getResetter());
    }

    public function testAutowireResetterAsCallable(): void
    {
        $autowire = AttributeReader::getAutowireAttribute(new ReflectionClass(Baz::class))->current();

        self::assertIsCallable($resetter = $autowire->getResetter());
        self::assertEquals([BazResetter::class, 'doReset'], $resetter);
    }
}
