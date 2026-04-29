<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader;

use Generator;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DefinitionsLoader::class)]
class DefinitionsLoaderContextTest extends TestCase
{
    #[DataProvider('dataSetContext')]
    public function testSetContext(iterable $context, string $getContextName, mixed $expectValue): void
    {
        $definitionsLoader = (new DefinitionsLoader())
            ->setConfiguratorContexts($context)
        ;

        $value = $definitionsLoader->definitionsConfigurator()->getContext($getContextName);

        self::assertSame($expectValue, $value);
    }

    public static function dataSetContext(): Generator
    {
        yield 'string val' => [
            'context' => ['APP_ENV' => 'prod'], 'getContextName' => 'APP_ENV', 'expectValue' => 'prod',
        ];

        $object = (object) ['APP_ENV' => 'prod', 'debug' => true];

        yield 'object val' => [
            'context' => ['core.object' => $object], 'getContextName' => 'core.object', 'expectValue' => $object,
        ];
    }

    public function testGetContextWithoutFallback(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('The context name \'bar\' does not exist.');

        $definitionsLoader = (new DefinitionsLoader())
            ->setConfiguratorContexts(['foo' => 'foo value'])
        ;

        $definitionsLoader->definitionsConfigurator()->getContext('bar');
    }

    public function testGetContextWithFallback(): void
    {
        $definitionsLoader = (new DefinitionsLoader())
            ->setConfiguratorContexts(['foo' => 'foo value'])
        ;

        $value = $definitionsLoader->definitionsConfigurator()->getContext('bar', static fn () => (object) ['bar' => true]);

        self::assertEquals((object) ['bar' => true], $value);
    }
}
