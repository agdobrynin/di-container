<?php

declare(strict_types=1);

namespace Tests\Traits\PsrContainer;

use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kaspi\DiContainer\Traits\PsrContainerTrait
 *
 * @internal
 */
class PsrContainerTest extends TestCase
{
    use PsrContainerTrait;

    public function testPsrContainerSet(): void
    {
        $container = new class implements ContainerInterface {
            public function get(string $id) {}

            public function has(string $id): bool {}
        };

        $this->assertInstanceOf(self::class, $this->setContainer($container));
        $this->assertInstanceOf(ContainerInterface::class, $this->getContainer());
    }

    public function testPsrContainerGetException(): void
    {
        $this->expectException(ContainerNeedSetExceptionInterface::class);
        $this->expectExceptionMessage('Need set container implementation');

        $this->getContainer();
    }
}
