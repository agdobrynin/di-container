<?php

declare(strict_types=1);

namespace Tests\Compiler\Compiler;

use ArrayIterator;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ValueEntry;
use Kaspi\DiContainer\Compiler\CompiledEntries;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\ContainerCompiler;
use Kaspi\DiContainer\Compiler\ContainerCompilerToFile;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum;
use Kaspi\DiContainer\Exception\CompiledContainerException;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ContainerCompiler::class)]
#[CoversClass(ContainerCompilerToFile::class)]
#[CoversClass(ValueEntry::class)]
#[CoversClass(CompiledEntry::class)]
#[CoversClass(Helper::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(CompiledContainerException::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(CompiledEntries::class)]
class CompiledExceptionStackTest extends TestCase
{
    private DiContainerInterface $container;

    public function setUp(): void
    {
        vfsStream::setup();

        $mockDiContainerDefinitions = $this->createMock(DiContainerDefinitionsInterface::class);

        $mockDiContainerDefinitions->method('getDefinitions')->willReturn(
            new ArrayIterator([
                'services.foo' => new DiDefinitionValue([]),
            ]),
        );

        $mockTransformer = $this->createMock(DiDefinitionTransformerInterface::class);
        $mockTransformer->method('transform')
            ->willThrowException(
                new DefinitionCompileException()
            )
        ;
        $compiledEntries = new CompiledEntries();

        $compiler = new ContainerCompiler('Container', $mockDiContainerDefinitions, $mockTransformer, InvalidBehaviorCompileEnum::RuntimeContainerException, $compiledEntries);

        $file = (new ContainerCompilerToFile(
            vfsStream::url('root'),
            $compiler,
            isExclusiveLockFile: false,
        ))
            ->compileToFile()
        ;

        require_once $file;

        $this->container = new ($compiler->getContainerFQN()->getFQN())();
    }

    public function tearDown(): void
    {
        unset($this->container);
    }

    public function testGenerateExceptionStack(): void
    {
        $this->expectException(CompiledContainerException::class);
        $this->expectExceptionMessage('The definition was not compiled for the container identifier \'services.foo\'');

        $this->container->get('services.foo');
    }

    public function testGenerateExceptionStackToString(): void
    {
        try {
            $this->container->get('services.foo');
        } catch (CompiledContainerException $e) {
            self::assertStringContainsString('Stack trace:'.PHP_EOL, (string) $e);
        }
    }
}
