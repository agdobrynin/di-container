<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs;

use Generator;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\DefaultPriorityMethodWrong\Baz;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\DefaultPriorityMethodWrong\Foo;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\Tag
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 *
 * @internal
 */
class TaggedAsDefaultPriorityMethodTest extends TestCase
{
    /**
     * @dataProvider dataProviderDefaultPriorityMethodWrongWithPhpAttribute
     */
    public function testGetDefaultPriorityMethodWrongWithPhpAttribute(string $tagName, array $definitions, string $method): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/via default priority method .+Baz::'.$method.'()/');

        $container = $this->createMock(DiContainerInterface::class);
        $container->method('getConfig')->willReturn(
            new DiContainerConfig(
                useAttribute: true,
            )
        );

        $container->method('getDefinitions')->willReturn($definitions);

        $t = new DiDefinitionTaggedAs($tagName, isLazy: false, priorityDefaultMethod: $method);
        $t->resolve($container);
    }

    public function dataProviderDefaultPriorityMethodWrongWithPhpAttribute(): Generator
    {
        yield 'return object' => [
            'tags.bat',
            [
                diAutowire(Foo::class),
                diAutowire(Baz::class),
            ],
            'getPriorityDefaultOne',
        ];

        yield 'return array' => [
            'tags.bat',
            [
                diAutowire(Foo::class),
                diAutowire(Baz::class),
            ],
            'getPriorityDefaultTwo',
        ];
    }

    /**
     * @dataProvider dataProviderDefaultPriorityMethodWrong
     */
    public function testGetDefaultPriorityMethodWrong(string $tagName, array $definitions, string $method): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/via default priority method .+Baz::'.$method.'()/');

        // Php attribute disabled.
        $container = $this->createMock(DiContainerInterface::class);

        $container->method('getDefinitions')->willReturn($definitions);

        $t = new DiDefinitionTaggedAs($tagName, isLazy: false, priorityDefaultMethod: $method);
        $t->resolve($container);
    }

    public function dataProviderDefaultPriorityMethodWrong(): Generator
    {
        yield 'return object' => [
            'tags.baz',
            [
                diAutowire(Foo::class)
                    ->bindTag('tags.foo'),
                diAutowire(Baz::class)
                    ->bindTag(
                        'tags.baz',
                        options: [
                            // This metadata will be replaced in DiDefinitionTaggedAs
                            'priority.default_method' => 'getPriorityDefaultTwo',
                        ]
                    ),
            ],
            'getPriorityDefaultOne',
        ];

        yield 'return array' => [
            'tags.baz',
            [
                diAutowire(Foo::class)
                    ->bindTag('tags.foo'),
                diAutowire(Baz::class)
                    ->bindTag('tags.baz'),
            ],
            'getPriorityDefaultTwo',
        ];
    }
}
