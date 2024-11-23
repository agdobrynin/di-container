<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\VariadicArg;

use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Tests\DiContainerCall\VariadicArg\Fixtures\WordHello;
use Tests\DiContainerCall\VariadicArg\Fixtures\WordInterface;
use Tests\DiContainerCall\VariadicArg\Fixtures\WordSuffix;
use Tests\DiContainerCall\VariadicArg\Fixtures\WordVariadicDiFactory;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diReference;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionReference
 * @covers \Kaspi\DiContainer\diReference
 *
 * @internal
 */
class CallFunctionWithVariadicArgTest extends TestCase
{
    public function testUserFunctionVariadicArgumentsPassByCallMethodWithDiReference(): void
    {
        $fn = static fn (
            WordInterface ...$word
        ) => $word;

        $definitions = [
            'services.word_hello' => diAutowire(WordHello::class),
            'services.word_suffix' => diAutowire(WordSuffix::class),
        ];
        $container = new DiContainer($definitions);

        $res = $container->call(
            $fn,
            [
                'word' => [
                    diReference('services.word_hello'),
                    diReference('services.word_suffix'),
                ],
            ]
        );

        $this->assertInstanceOf(WordHello::class, $res[0]);
        $this->assertInstanceOf(WordSuffix::class, $res[1]);
    }

    public function testUserFunctionVariadicArgumentsByAttributeManyTimes(): void
    {
        $fn = static fn (
            #[Inject('services.word_suffix')]
            #[Inject('services.word_hello')]
            WordInterface ...$word
        ) => $word;

        $definitions = [
            'services.word_hello' => diAutowire(WordHello::class),
            'services.word_suffix' => diAutowire(WordSuffix::class),
        ];
        $container = new DiContainer($definitions, new DiContainerConfig(useZeroConfigurationDefinition: false));

        $res = $container->call($fn);

        $this->assertInstanceOf(WordSuffix::class, $res[0]);
        $this->assertInstanceOf(WordHello::class, $res[1]);
    }

    public function testUserFunctionVariadicArgumentsInjectByContainerIdToOneToMany(): void
    {
        $fn = static fn (
            #[Inject('services.words')]
            WordInterface ...$word
        ) => $word;

        $definitions = [
            'services.words' => diAutowire(WordVariadicDiFactory::class),
        ];
        $container = new DiContainer($definitions, new DiContainerConfig());

        $res = $container->call($fn);

        $this->assertInstanceOf(WordSuffix::class, $res[0]);
        $this->assertInstanceOf(WordHello::class, $res[1]);
    }

    public function testUserFunctionVariadicArgumentsByInjectWithIdAsReferenceToOneToMany(): void
    {
        $fn = static fn (
            WordInterface ...$word
        ) => $word;

        $definitions = [
            'services.words' => diAutowire(WordVariadicDiFactory::class),
        ];
        $container = new DiContainer($definitions, new DiContainerConfig());

        $res = $container->call($fn, ['word' => diReference('services.words')]);

        $this->assertInstanceOf(WordSuffix::class, $res[0]);
        $this->assertInstanceOf(WordHello::class, $res[1]);
    }

    public function testUserFunctionVariadicArgumentsByInjectWithIdAsDiFactoryOneToMany(): void
    {
        $fn = static fn (
            #[Inject(WordVariadicDiFactory::class)]
            WordInterface ...$word
        ) => $word;

        $container = new DiContainer(config: new DiContainerConfig());

        $res = $container->call($fn);

        $this->assertInstanceOf(WordSuffix::class, $res[0]);
        $this->assertInstanceOf(WordHello::class, $res[1]);
    }
}
