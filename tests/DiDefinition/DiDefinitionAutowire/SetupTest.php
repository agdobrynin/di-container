<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SetupClass;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 *
 * @internal
 */
class SetupTest extends TestCase
{
    public function testSetupSuccess(): void
    {
        $def = (new DiDefinitionAutowire(SetupClass::class))
            ->setup('setName', newName: 'Vasiliy') // first set name
            ->setup('setName', 'Piter') // override set name
            ->setup('setParameters', paramName: 'key1', parameters: ['One', 'Two', 'Three'])
            ->setup('setParameters', 'key2', ['Four', 'Five', 'Six'])
        ;

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
            ->setup('methodNotExist')
        ;

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('The method "methodNotExist" does not exist');

        $def->invoke();
    }

    public function testSetupWithoutParameters(): void
    {
        $def = (new DiDefinitionAutowire(SetupClass::class))
            ->setup('incInc')
            ->setup('incInc')
            ->setup('incInc')
        ;

        $class = $def->invoke();

        $this->assertEquals(3, $class->getInc());
    }
}
