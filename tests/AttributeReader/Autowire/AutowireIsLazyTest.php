<?php

declare(strict_types=1);

namespace Tests\AttributeReader\Autowire;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\AttributeReader\Autowire\Fixtures\Bar;
use Tests\AttributeReader\Autowire\Fixtures\FooLazy;

/**
 * @internal
 */
#[CoversClass(Autowire::class)]
#[CoversClass(AttributeReader::class)]
class AutowireIsLazyTest extends TestCase
{
    public function testDefaultIsLazy(): void
    {
        /** @var Autowire[] $attrs */
        $attrs = [...AttributeReader::getAutowireAttribute(new ReflectionClass(Bar::class))];

        self::assertFalse($attrs[0]->isLazy);
    }

    #[RequiresPhp('< 8.4')]
    public function testPhpLessThenVersion81(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('requires PHP version 8.4 or higher');

        [...AttributeReader::getAutowireAttribute(new ReflectionClass(FooLazy::class))];
    }

    #[RequiresPhp('>= 8.4')]
    public function testPhpHigherOrEqualVersion84(): void
    {
        /** @var Autowire $attrs */
        $attrs = [...AttributeReader::getAutowireAttribute(new ReflectionClass(FooLazy::class))];

        self::assertTrue($attrs[0]->isLazy);
    }
}
