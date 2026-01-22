<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Enum\SetupConfigureMethod;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\ClassWithConstructDestruct;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SetupByAttributeWithArgumentAsReference;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SetupClass;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SetupClassByAttribute;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SomeClass;

use function Kaspi\DiContainer\diValue;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diValue')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(\Kaspi\DiContainer\Attributes\Setup::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(SetupConfigureMethod::class)]
#[CoversClass(Helper::class)]
class SetupTest extends TestCase
{
    public function testSetupSuccess(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);

        $def = (new DiDefinitionAutowire(SetupClass::class))
            ->setup('setName', newName: diValue('Vasiliy')) // first set name
            ->setup('setName', diValue('Piter')) // override set name
            ->setup('setParameters', paramName: diValue('key1'), parameters: ['One', 'Two', 'Three'])
            ->setup('setParameters', 'key2', ['Four', 'Five', 'Six'])
        ;

        /**
         * @var SetupClass $class
         */
        $class = $def->resolve($mockContainer);

        $this->assertInstanceOf(SetupClass::class, $class);
        $this->assertEquals('Piter', $class->getName());
        $this->assertEquals('Vasiliy', $class->getPreviousName());
        $this->assertCount(2, $class->getParameters());
        $this->assertEquals(['key1' => ['One', 'Two', 'Three'], 'key2' => ['Four', 'Five', 'Six']], $class->getParameters());
    }

    public function testSetupMethodNotExist(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/The setter method.+\SetupClass::methodNotExist\(\)" does not exist/');

        $def = (new DiDefinitionAutowire(SetupClass::class))
            ->setup('methodNotExist')
        ;

        $def->resolve($this->createMock(DiContainerInterface::class));
    }

    public function testSetupWithoutParameters(): void
    {
        $def = (new DiDefinitionAutowire(SetupClass::class))
            ->setup('incInc')
            ->setup('incInc')
            ->setup('incInc')
        ;

        $class = $def->resolve($this->createMock(DiContainerInterface::class));

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
            ->setup('incInc')// override by php attribute #[Setup]
            ->setup('incInc') // override by php attribute #[Setup]
            ->setup('incInc') // override by php attribute #[Setup]
            ->setup('setParameters', 'key1', ['One', 'Two', 'Three']) // override by php attribute #[Setup]
            ->setup('setParameters', 'key2', ['X', 'Y', 'Z']) // override by php attribute #[Setup]
        ;

        /** @var SetupClassByAttribute $class */
        $class = $def->resolve($mockContainer);

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
        // Default class SomeClass must exist in container
        $mockContainer->method('has')
            ->with(SomeClass::class)
            ->willReturn(true)
        ;

        $def = (new DiDefinitionAutowire(SetupByAttributeWithArgumentAsReference::class))
            ->setup('setSomeClassAsContainerIdentifier', someClass: null) // overrode by php attribute on method
        ;

        /** @var SetupByAttributeWithArgumentAsReference $class */
        $class = $def->resolve($mockContainer);

        self::assertInstanceOf(SomeClass::class, $class->getSomeClass());
        self::assertEquals('baz', $class->dependencyAutoResolve->getValue());
        self::assertEquals('foo', $class->getSomeClass()->getValue());

        self::assertEquals('string from container', $class->getAnyAsContainerIdentifier());
        self::assertEquals('@@la-la-la', $class->getAnyAsEscapedString());
        self::assertEquals('la-la-la', $class->getAnyAsString());
    }

    #[DataProvider('dataProviderSetupOnMethod')]
    public function testSetupOnMethod(string $class, string $method): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot use ".+'.$method.'\(\)" as setter/');

        $def = (new DiDefinitionAutowire($class))
            ->setup($method)
        ;

        $def->resolve($this->createMock(DiContainerInterface::class));
    }

    public static function dataProviderSetupOnMethod(): Generator
    {
        yield 'on construct setup method' => [ClassWithConstructDestruct::class, '__construct'];

        yield 'on destruct setup method' => [ClassWithConstructDestruct::class, '__destruct'];
    }
}
