<?php

declare(strict_types=1);

namespace Tests\Unit\Definition;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Unit\Definition\Fixtures\SimpleService;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diValue;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionSimple
 * @covers \Kaspi\DiContainer\diValue
 *
 * @internal
 */
class DefinitionHelperFunctionTest extends TestCase
{
    public function testDiValueFunction(): void
    {
        $container = (new DiContainerFactory())->make([
            'log' => diValue(['a' => 'aaa']),
        ]);

        $this->assertEquals(['a' => 'aaa'], $container->get('log'));
    }

    public function testDiValueFunctionWithoutContainerIdentifier(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        (new DiContainerFactory())->make([
            diValue(['a' => 'aaa']),
        ]);
    }

    public function testDiAutowireFunctionWithEmptyIdentifier(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        (new DiContainerFactory())->make([
            diAutowire('   '),
        ]);
    }

    public function testDiAutowireFunction(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(SimpleService::class),
        ]);

        $class = $container->get(SimpleService::class);

        $this->assertInstanceOf(SimpleService::class, $class);
    }

    public function testDiAutowireFunctionNonExistClass(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire('non-exist-class'),
        ]);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('does not exist');

        $container->get('non-exist-class');
    }
}
