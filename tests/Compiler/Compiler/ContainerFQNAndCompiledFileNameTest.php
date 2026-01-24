<?php

declare(strict_types=1);

namespace Tests\Compiler\Compiler;

use Generator;
use InvalidArgumentException;
use Kaspi\DiContainer\Compiler\ContainerCompiler;
use Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntriesInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ContainerCompiler::class)]
class ContainerFQNAndCompiledFileNameTest extends TestCase
{
    private DiContainerDefinitionsInterface $mockContainerDefinitions;
    private DiDefinitionTransformerInterface $mockTransformer;
    private CompiledEntriesInterface $compiledEntries;

    public function setUp(): void
    {
        $this->mockContainerDefinitions = $this->createMock(DiContainerDefinitionsInterface::class);
        $this->mockTransformer = $this->createMock(DiDefinitionTransformerInterface::class);
        $this->compiledEntries = $this->createMock(CompiledEntriesInterface::class);
    }

    public function tearDown(): void
    {
        unset($this->mockContainerDefinitions, $this->mockTransformer, $this->compiledEntries);
    }

    #[DataProvider('dataProviderContainerClassInvalid')]
    public function testContainerClassInvalid(string $containerClass, $exceptionMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        (new ContainerCompiler(
            $containerClass,
            $this->mockContainerDefinitions,
            $this->mockTransformer,
            InvalidBehaviorCompileEnum::ExceptionOnCompile,
            $this->compiledEntries,
        ))
            ->getContainerFQN()
        ;
    }

    public static function dataProviderContainerClassInvalid(): Generator
    {
        yield 'only class name' => [
            '123Container',
            'The container class name "123Container" is invalid. Got fully qualified class name: "123Container".',
        ];

        yield 'namespace with ending slashes' => [
            '123Container\\\\',
            'The container class name "" is invalid. Got fully qualified class name: "123Container\\\".',
        ];

        yield 'namespace success, class name fail' => [
            'App\123Container',
            'The container class name "123Container" is invalid. Got fully qualified class name: "App\123Container".',
        ];

        yield 'namespace fail, class name success' => [
            '12AAA\Core\Container',
            'The namespace "12AAA\Core" in container class name must be compatible with PSR-4. Got fully qualified class name: "12AAA\Core\Container".',
        ];
    }

    #[DataProvider('dataProviderContainerClassSuccess')]
    public function testSuccess(string $containerClass, string $fullyQualifiedClassName, string $className, string $namespace): void
    {
        $fqcn = ($compiledContainer = new ContainerCompiler(
            $containerClass,
            $this->mockContainerDefinitions,
            $this->mockTransformer,
            InvalidBehaviorCompileEnum::ExceptionOnCompile,
            $this->compiledEntries,
        ))
            ->getContainerFQN()
        ;

        self::assertEquals($fullyQualifiedClassName, $fqcn->getFQN());
        self::assertEquals($className, $fqcn->getClass());
        self::assertEquals($namespace, $fqcn->getNamespace());
        // cached data
        self::assertEquals($fullyQualifiedClassName, $compiledContainer->getContainerFQN()->getFQN());
    }

    public static function dataProviderContainerClassSuccess(): Generator
    {
        yield 'class name with root namespace' => [
            '\Container',
            '\Container',
            'Container',
            '',
        ];

        yield 'class name with namespace' => [
            '\App\Core\Container',
            '\App\Core\Container',
            'Container',
            'App\Core',
        ];
    }
}
