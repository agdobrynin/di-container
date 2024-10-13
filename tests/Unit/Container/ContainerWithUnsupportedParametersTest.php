<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Fixtures\Classes\ClassWithEmptyType;
use Tests\Fixtures\Classes\ClassWithUnionType;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerDefinition
 * @covers \Kaspi\DiContainer\DiContainerFactory
 *
 * @internal
 */
class ContainerWithUnsupportedParametersTest extends TestCase
{
    public function testEmptyTypeHint(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Unsupported parameter type');

        (new DiContainerFactory())->make([
            'dependency' => static fn () => new \ReflectionClass($this),
        ])->get(ClassWithEmptyType::class);
    }

    public function testUnionTypeHint(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Unsupported parameter type');

        (new DiContainerFactory())->make()->get(ClassWithUnionType::class);
    }
}
