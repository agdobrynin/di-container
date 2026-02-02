<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Generator;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\ClassWithConstructDestruct;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SetupByAttributeWithArgumentAsReference;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SetupClass;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SetupClassByAttribute;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SomeClass;

use function Kaspi\DiContainer\diValue;

/**
 * @covers \Kaspi\DiContainer\Attributes\Setup
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet::getDefinition
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diValue
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait::getParameterType
 *
 * @internal
 */
class SetupTest extends TestCase
{
    public function testSetupSuccess(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);

        $def = (new DiDefinitionAutowire(SetupClass::class))
            ->setup('setName', ['newName' => diValue('Vasiliy')]) // first set name
            ->setup('setName', [diValue('Piter')]) // override set name
            ->setup('setParameters', ['paramName' => diValue('key1'), 'parameters' => ['One', 'Two', 'Three']])
            ->setup('setParameters', ['key2', ['Four', 'Five', 'Six']])
        ;
        $def->setContainer($mockContainer);

        /**
         * @var SetupClass $class
         */
        $class = $def->invoke();

        $this->assertInstanceOf(SetupClass::class, $class);
        $this->assertEquals('Piter', $class->getName());
        $this->assertEquals('Vasiliy', $class->getPreviousName());
        $this->assertCount(2, $class->getParameters());
        $this->assertEquals(['key1' => ['One', 'Two', 'Three'], 'key2' => ['Four', 'Five', 'Six']], $class->getParameters());
    }

    public function testSetupMethodNotExist(): void
    {
        $def = (new DiDefinitionAutowire(SetupClass::class))
            ->setContainer($this->createMock(DiContainerInterface::class))
            ->setup('methodNotExist')
        ;

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/The setter method.+\SetupClass::methodNotExist\(\)" does not exist/');

        $def->invoke();
    }

    public function testSetupWithoutParameters(): void
    {
        $def = (new DiDefinitionAutowire(SetupClass::class))
            ->setContainer($this->createMock(DiContainerInterface::class))
            ->setup('incInc')
            ->setup('incInc')
            ->setup('incInc')
        ;

        $class = $def->invoke();

        $this->assertEquals(3, $class->getInc());
    }

    public function testSetupByAttribute(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;

        // #[Setup] on `incInc` method call 4 times.
        // #[Setup] on `setParameters` method call 2 times with arguments.
        $def = (new DiDefinitionAutowire(SetupClassByAttribute::class))
            ->setContainer($mockContainer)
            ->setup('incInc')// override by php attribute #[Setup]
            ->setup('incInc') // override by php attribute #[Setup]
            ->setup('incInc') // override by php attribute #[Setup]
            ->setup('setParameters', ['key1', ['One', 'Two', 'Three']]) // override by php attribute #[Setup]
            ->setup('setParameters', ['key2', ['X', 'Y', 'Z']]) // override by php attribute #[Setup]
        ;

        /** @var SetupClassByAttribute $class */
        $class = $def->invoke();

        self::assertEquals(4, $class->getInc());
        self::assertSame(
            [
                'abc' => ['one', 'two', 'three'],
                'path' => ['/tmp', '/var/cache'],
            ],
            $class->getParameters()
        );
    }

    public function testSetupByAttributeStringArgumentAsDiGet(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;
        $mockContainer->method('get')
            ->willReturnMap([
                ['services.some_class', new SomeClass('foo')],
                [SomeClass::class, new SomeClass('baz')],
                ['services.any_string', 'string from container'],
            ])
        ;

        $def = (new DiDefinitionAutowire(SetupByAttributeWithArgumentAsReference::class))
            ->setup('setSomeClassAsContainerIdentifier', ['someClass' => null]) // overrode by php attribute on method
            ->setContainer($mockContainer)
        ;

        /** @var SetupByAttributeWithArgumentAsReference $class */
        $class = $def->invoke();

        self::assertInstanceOf(SomeClass::class, $class->getSomeClass());
        self::assertEquals('baz', $class->dependencyAutoResolve->getValue());
        self::assertEquals('foo', $class->getSomeClass()->getValue());

        self::assertEquals('string from container', $class->getAnyAsContainerIdentifier());
        self::assertEquals('@la-la-la', $class->getAnyAsEscapedString());
        self::assertEquals('la-la-la', $class->getAnyAsString());
    }

    public function dataProviderSetupOnMethod(): Generator
    {
        yield 'on construct setup method' => [ClassWithConstructDestruct::class, '__construct'];

        yield 'on destruct setup method' => [ClassWithConstructDestruct::class, '__destruct'];
    }

    /**
     * @dataProvider dataProviderSetupOnMethod
     */
    public function testSetupOnMethod(string $class, string $method): void
    {
        $def = (new DiDefinitionAutowire($class))
            ->setup($method)
            ->setContainer($this->createMock(DiContainerInterface::class))
        ;

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot use.+'.$method.'\(\) as setter/');

        $def->invoke();
    }
}
