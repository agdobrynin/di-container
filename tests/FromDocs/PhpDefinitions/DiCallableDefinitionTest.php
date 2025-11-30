<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\ServiceOne;

use function Kaspi\DiContainer\diCallable;

/**
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\Helper
 *
 * @internal
 */
class DiCallableDefinitionTest extends TestCase
{
    public function testDiCallableDefinition(): void
    {
        $definitions = [
            'services.one' => diCallable(
                definition: static fn () => new ServiceOne(apiKey: 'my-api-key'),
                isSingleton: true,
            ),
        ];

        $container = (new DiContainerFactory())->make($definitions);

        $this->assertInstanceOf(ServiceOne::class, $container->get('services.one'));
        $this->assertEquals('my-api-key', $container->get('services.one')->getApiKey());
        $this->assertSame($container->get('services.one'), $container->get('services.one'));
    }

    public function testCallbackDefinition(): void
    {
        $definitions = [
            'services.one' => static fn () => new ServiceOne(apiKey: 'my-api-key'),
        ];

        $container = (new DiContainerFactory())->make($definitions);

        $this->assertInstanceOf(ServiceOne::class, $container->get('services.one'));
        $this->assertEquals('my-api-key', $container->get('services.one')->getApiKey());
        $this->assertNotSame($container->get('services.one'), $container->get('services.one'));
    }
}
