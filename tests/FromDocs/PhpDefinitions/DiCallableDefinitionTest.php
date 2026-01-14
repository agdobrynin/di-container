<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\ServiceOne;

use function Kaspi\DiContainer\diCallable;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diCallable')]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(Helper::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
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
