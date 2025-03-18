<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionInvokableWrapper;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionInvokableWrapper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionInvokableWrapper\Fixtures\One;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionInvokableWrapper
 */
class DiDefinitionInvokableWrapperTest extends TestCase
{
    public function testInvoke(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->method('get')
            ->with(One::class)->willReturn(
                new One()
            )
        ;

        $def = (new DiDefinitionInvokableWrapper(new DiDefinitionAutowire(One::class)))
            ->setContainer($mockContainer)
        ;

        $this->assertInstanceOf(DiDefinitionAutowire::class, $def->getDefinition());
        $this->assertNull($def->isSingleton());
        $this->assertInstanceOf(One::class, $def->invoke());
    }
}
