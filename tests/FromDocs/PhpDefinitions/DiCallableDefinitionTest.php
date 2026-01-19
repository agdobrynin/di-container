<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\ServiceOne;

use function Kaspi\DiContainer\diCallable;

/**
 * @internal
 */
#[CoversNothing]
class DiCallableDefinitionTest extends TestCase
{
    public function testDiCallableDefinition(): void
    {
        $definitions = static function () {
            yield 'services.one' => diCallable(
                definition: static fn () => new ServiceOne(apiKey: 'my-api-key'),
                isSingleton: true,
            );
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($definitions())
            ->build()
        ;

        self::assertInstanceOf(ServiceOne::class, $container->get('services.one'));
        self::assertEquals('my-api-key', $container->get('services.one')->getApiKey());
        self::assertSame($container->get('services.one'), $container->get('services.one'));
    }

    public function testCallbackDefinition(): void
    {
        $definitions = [
            'services.one' => static fn () => new ServiceOne(apiKey: 'my-api-key'),
        ];

        $container = (new DiContainerBuilder())
            ->addDefinitions($definitions)
            ->build()
        ;

        self::assertInstanceOf(ServiceOne::class, $container->get('services.one'));
        self::assertEquals('my-api-key', $container->get('services.one')->getApiKey());
        self::assertNotSame($container->get('services.one'), $container->get('services.one'));
    }
}
