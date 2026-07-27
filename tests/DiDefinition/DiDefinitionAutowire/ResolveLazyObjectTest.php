<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\FooLazy;

/**
 * @internal
 */
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
class ResolveLazyObjectTest extends TestCase
{
    #[RequiresPhp('< 8.4')]
    public function testLazyNotAvailable(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Lazy object require PHP 8.4 or higher');

        (new DiDefinitionAutowire(FooLazy::class, isLazy: true))
            ->resolve($this->createMock(DiContainerInterface::class))
        ;
    }

    #[RequiresPhp('>= 8.4')]
    public function testLazyIsSuccess(): void
    {
        $foo = (new DiDefinitionAutowire(FooLazy::class, isLazy: true))
            ->resolve($this->createMock(DiContainerInterface::class))
        ;

        self::assertInstanceOf(FooLazy::class, $foo);

        $fooReflection = new ReflectionClass($foo);

        self::assertTrue($fooReflection->isUninitializedLazyObject($foo));

        self::assertEquals('Lorem ipsum', $foo->getVal());

        self::assertFalse($fooReflection->isUninitializedLazyObject($foo));
    }
}
