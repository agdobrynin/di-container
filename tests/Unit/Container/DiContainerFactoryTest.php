<?php

declare(strict_types=1);

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
 */
class DiContainerFactoryTest extends TestCase
{
    public function testFactory(): void
    {
        $c = (new DiContainerFactory())->make();

        $this->assertInstanceOf(DiContainerInterface::class, $c);
    }
}
