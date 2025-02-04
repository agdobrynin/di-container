<?php

declare(strict_types=1);

namespace Tests\Traits\TagsTrait;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\TagsTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\Traits\DiContainerTrait
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 * @covers \Kaspi\DiContainer\Traits\TagsTrait
 */
class BindTagTest extends TestCase
{
    use TagsTrait;

    public function testBindTagOverrideOptions(): void
    {
        $this->bindTag(
            'tag1',
            options: ['priority' => 10, 'defaultPriorityMethod' => 'myMethod'],
            priority: 1000,
            defaultPriorityMethod: 'getPriority'
        );

        $this->assertEquals(
            ['priority' => 1000, 'defaultPriorityMethod' => 'getPriority'],
            $this->getTag('tag1')
        );

        // Trait not assign to DiDefinitionAutowire::class
        $this->assertEquals(1000, $this->getOptionPriority('tag1'));
    }

    public function testGetPriorityByMethod(): void
    {
        $class = new class {
            public static function getPriority(): int
            {
                return 50;
            }
        };
        $mockContainer = $this->createMock(DiContainerInterface::class);

        $definition = (new DiDefinitionAutowire(new \ReflectionClass($class)))
            ->setContainer($mockContainer)
            ->bindTag('tag1', priority: 1000, defaultPriorityMethod: 'getPriority')
        ;

        $this->assertEquals(50, $definition->getOptionPriority('tag1'));
    }

    public function dataProvidePriorityByMethod(): \Generator
    {
        yield 'empty string' => [
            new class {},
            '',
            'must be non-empty string',
        ];

        yield 'string with empty' => [
            new class {},
            '   ',
            'must be non-empty string',
        ];

        yield 'method not exist' => [
            new class {},
            'getPriority',
            'method "getPriority" does not exist',
        ];

        yield 'named method with spaces not exist' => [
            new class {
                public static function getPriority(): int
                {
                    return 50;
                }
            },
            '   getPriority  ',
            'method "   getPriority  " does not exist',
        ];

        yield 'named method not equal into class' => [
            new class {
                public static function priority(): int
                {
                    return 50;
                }
            },
            'getPriority',
            'method "getPriority" does not exist',
        ];

        yield 'method is private' => [
            new class {
                private static function getPriority(): int
                {
                    return 50;
                }
            },
            'getPriority',
            'method "getPriority" must be declared as public',
        ];

        yield 'method is protect' => [
            new class {
                private static function getPriority(): int
                {
                    return 50;
                }
            },
            'getPriority',
            'method "getPriority" must be declared as public',
        ];

        yield 'method is not static' => [
            new class {
                public function getPriority(): int
                {
                    return 50;
                }
            },
            'getPriority',
            'method "getPriority" must be declared as static',
        ];
    }

    /**
     * @dataProvider dataProvidePriorityByMethod
     */
    public function testGetPriorityByMethodExceptionEmptyMethodName(object $class, string $defaultPriorityMethod, string $exceptionMessage): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);

        $definition = (new DiDefinitionAutowire(new \ReflectionClass($class)))
            ->setContainer($mockContainer)
            ->bindTag('tag1', defaultPriorityMethod: $defaultPriorityMethod)
        ;

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage($exceptionMessage);

        $definition->getOptionPriority('tag1');
    }
}
