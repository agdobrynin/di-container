<?php

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\Autowired
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\KeyGeneratorForNamedParameter
 */
class DiContainerFactoryTest extends TestCase
{
    public function testFactory(): void
    {
        $c = DiContainerFactory::make();

        $this->assertInstanceOf(DiContainerInterface::class, $c);
    }
}
