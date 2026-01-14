<?php

declare(strict_types=1);

namespace Tests\Compiler\DiDefinitionTransformer;

use Generator;
use Kaspi\DiContainer\Compiler\CompilableDefinition\CallableEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\FactoryEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\GetEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ObjectEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ProxyClosureEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\TaggedAsEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ValueEntry;
use Kaspi\DiContainer\Compiler\DiDefinitionTransformer;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionCallableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionFactoryInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionProxyClosureInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionValueInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderClosureCodeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
#[CoversClass(ValueEntry::class)]
#[CoversClass(GetEntry::class)]
#[CoversClass(TaggedAsEntry::class)]
#[CoversClass(ProxyClosureEntry::class)]
#[CoversClass(CallableEntry::class)]
#[CoversClass(ObjectEntry::class)]
#[CoversClass(FactoryEntry::class)]
#[CoversClass(DiDefinitionTransformer::class)]
class DiDefinitionTransformerTest extends TestCase
{
    private object $closureParser;
    private object $diContainerDefinitions;
    private DiDefinitionTransformer $transformer;

    public function setUp(): void
    {
        $this->closureParser = $this->createMock(FinderClosureCodeInterface::class);
        $this->diContainerDefinitions = $this->createMock(DiContainerDefinitionsInterface::class);
        $this->transformer = new DiDefinitionTransformer($this->closureParser);
    }

    public function tearDown(): void
    {
        unset($this->closureParser, $this->diContainerDefinitions, $this->transformer);
    }

    #[DataProvider('dataProviderSupportCompilableDefinitions')]
    public function testSupportCompilableDefinitions(mixed $definitionInterface, string $expectClass): void
    {
        $mockDefinition = $this->createMock($definitionInterface);

        self::assertInstanceOf($expectClass, $this->transformer->transform($mockDefinition, $this->diContainerDefinitions));
    }

    public static function dataProviderSupportCompilableDefinitions(): Generator
    {
        yield 'DiDefinitionValue' => [
            DiDefinitionValueInterface::class,
            ValueEntry::class,
        ];

        yield 'DiDefinitionGet' => [
            DiDefinitionLinkInterface::class,
            GetEntry::class,
        ];

        yield 'DiDefinitionTaggedAs' => [
            DiDefinitionTaggedAsInterface::class,
            TaggedAsEntry::class,
        ];

        yield 'DiDefinitionProxyClosure' => [
            DiDefinitionProxyClosureInterface::class,
            ProxyClosureEntry::class,
        ];

        yield 'DiDefinitionCallable' => [
            DiDefinitionCallableInterface::class,
            CallableEntry::class,
        ];

        yield 'DiDefinitionAutowire' => [
            DiDefinitionAutowireInterface::class,
            ObjectEntry::class,
        ];

        yield 'DiDefinitionFactory' => [
            DiDefinitionFactoryInterface::class,
            FactoryEntry::class,
        ];
    }

    public function testUnsupportedDefinitionWithoutFallback(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Unsupported definition type "stdClass"');

        $this->transformer->transform(new stdClass(), $this->diContainerDefinitions);
    }

    public function testUnsupportedDefinitionWithFallback(): void
    {
        $compilableDefinotion = $this->transformer->transform(
            ['name' => 'foo'],
            $this->diContainerDefinitions,
            static fn (mixed $definition, DiContainerDefinitionsInterface $diContainerDefinitions) => new ValueEntry($definition)
        );

        self::assertEquals(['name' => 'foo'], $compilableDefinotion->getDiDefinition());
    }

    public function testGetClosureParser(): void
    {
        self::assertInstanceOf(FinderClosureCodeInterface::class, $this->transformer->getClosureParser());
    }
}
