<?php

declare(strict_types=1);

namespace Tests\Compiler\Compiler;

use Generator;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ValueEntry;
use Kaspi\DiContainer\Compiler\CompiledEntries;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\ContainerCompiler;
use Kaspi\DiContainer\Compiler\DiContainerDefinitions;
use Kaspi\DiContainer\Compiler\DiDefinitionTransformer;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerNullConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\IdsIteratorInterface;
use Kaspi\DiContainer\Interfaces\DiContainerGetterDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderClosureCodeInterface;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;

use function bin2hex;
use function random_bytes;

/**
 * @internal
 */
#[CoversClass(DiContainerDefinitions::class)]
#[CoversClass(ContainerCompiler::class)]
#[CoversClass(CompiledEntry::class)]
#[CoversClass(Helper::class)]
#[CoversClass(NotFoundException::class)]
#[CoversClass(ValueEntry::class)]
#[CoversClass(DiDefinitionTransformer::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerNullConfig::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(CompiledEntries::class)]
class CompileTest extends TestCase
{
    private DiDefinitionTransformerInterface $mockTransformer;

    public function setUp(): void
    {
        $this->mockTransformer = $this->createMock(DiDefinitionTransformerInterface::class);
    }

    public function tearDown(): void
    {
        unset($this->mockTransformer);
    }

    public function testInvalidDefinitionCompile(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('The definition was not found via container identifier "foo"');

        $container = $this->createMockForIntersectionOfInterfaces([
            DiContainerGetterDefinitionInterface::class,
            DiContainerInterface::class,
        ]);
        $container->method('getDefinition')
            ->with('foo')
            ->willThrowException(new NotFoundException(id: 'foo'))
        ;

        $idsIter = $this->createMock(IdsIteratorInterface::class);
        $idsIter->method('current')->willReturn('foo');

        $containerDefinitions = new DiContainerDefinitions($container, $idsIter);
        $compiledEntries = new CompiledEntries();

        $compiler = new ContainerCompiler(
            'App\Container',
            $containerDefinitions,
            $this->mockTransformer,
            $compiledEntries,
            InvalidBehaviorCompileEnum::ExceptionOnCompile,
        );

        $compiler->compile();
    }

    public function testUniqueMethodNameForEntryCompile(): void
    {
        $container = $this->createMockForIntersectionOfInterfaces([
            DiContainerGetterDefinitionInterface::class,
            DiContainerInterface::class,
        ]);
        $container->method('getDefinitions')
            ->willReturnCallback(static function (): Generator {
                yield 'Container' => new DiDefinitionValue('Lorem ipsum');
            })
        ;

        $idsIter = $this->createMock(IdsIteratorInterface::class);

        $containerDefinitions = new DiContainerDefinitions($container, $idsIter);
        $transformer = new DiDefinitionTransformer($this->createMock(FinderClosureCodeInterface::class));
        $compiledEntries = new CompiledEntries();

        /*
         * Compiler generate private method `resolve_container()` for class `__NAMESPACE__.'\Container'` as predefined.
         * When the compiler receives the `Container` identifier from the container,
         * it will have to check the uniqueness of the names of the already prepared method names and,
         * if such a name is already taken, then generate a unique method name for the received identifier.
         */
        $containerClass = __NAMESPACE__.'\Container';

        $compiler = new ContainerCompiler(
            $containerClass,
            $containerDefinitions,
            $transformer,
            $compiledEntries,
            InvalidBehaviorCompileEnum::ExceptionOnCompile,
        );

        $containerFile = 'Container'.bin2hex(random_bytes(8)).'.php';

        vfsStream::setup(structure: [
            $containerFile => $compiler->compile(),
        ]);

        require_once vfsStream::url('root/'.$containerFile);

        $container = new $containerClass();

        self::assertEquals('Lorem ipsum', $container->get('Container'));
        self::assertInstanceOf(ContainerInterface::class, $container->get($containerClass));

        $reflectionClass = new ReflectionClass($container);

        self::assertTrue($reflectionClass->hasMethod('resolve_container'));
        self::assertTrue($reflectionClass->hasMethod('resolve_container1'));
    }
}
