<?php

declare(strict_types=1);

namespace Tests\Traits\DiContainer;

use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Traits\DiContainerTrait
 *
 * @internal
 */
class DiContainerTest extends TestCase
{
    use DiContainerTrait;

    public function testDiContainerSet(): void
    {
        $container = new class implements DiContainerInterface {
            public function get(string $id): mixed {}

            public function has(string $id): bool {}

            public function getDefinitions(): iterable {}

            public function getConfig(): ?DiContainerConfigInterface
            {
                return null;
            }
        };

        $this->assertInstanceOf(self::class, $this->setContainer($container));
    }

    public function testDiContainerGetException(): void
    {
        $this->expectException(ContainerNeedSetExceptionInterface::class);
        $this->expectExceptionMessage('Need set container implementation');

        $this->getContainer();
    }
}
