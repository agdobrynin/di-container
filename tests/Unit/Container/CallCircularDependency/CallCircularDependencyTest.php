<?php

declare(strict_types=1);

namespace Tests\Unit\Container\CallCircularDependency;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Exception\CallCircularDependency;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Container\CallCircularDependency\Fixtures\FirstClass;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerDefinition
 * @covers \Kaspi\DiContainer\DiContainerFactory
 *
 * @internal
 */
class CallCircularDependencyTest extends TestCase
{
    public function testCallCircularDependencySimpleValue(): void
    {
        $this->expectException(CallCircularDependency::class);

        (new DiContainerFactory())->make(
            [
                'inject1' => '@inject2',
                'inject2' => '@inject3',
                'inject3' => '@inject1',
            ]
        )->get('inject1');
    }

    public function testCallCircularDependencyInClass(): void
    {
        $this->expectException(CallCircularDependency::class);

        (new DiContainerFactory())->make()->get(FirstClass::class);
    }
}
