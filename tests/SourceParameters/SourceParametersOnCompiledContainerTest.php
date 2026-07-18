<?php

declare(strict_types=1);

namespace Tests\SourceParameters;

use Kaspi\DiContainer\Compiler\CompilableDefinition\ContainerParametersEntry;
use Kaspi\DiContainer\Compiler\CompiledEntries;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\ContainerCompiler;
use Kaspi\DiContainer\Compiler\ContainerCompilerToFile;
use Kaspi\DiContainer\Compiler\DiContainerDefinitions;
use Kaspi\DiContainer\Compiler\DiDefinitionTransformer;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\Compiler\IdsIterator;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerNullConfig;
use Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Exception\ParameterNotFoundException;
use Kaspi\DiContainer\Finder\FinderClosureCode;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterNotFoundExceptionInterface;
use Kaspi\DiContainer\Parameters\AbstractSourceParameters;
use Kaspi\DiContainer\Parameters\DeferredSourceParameters;
use Kaspi\DiContainer\Parameters\ImmediateSourceParameters;
use Kaspi\DiContainer\Parameters\SourceParameterItem;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\DeferredSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

use function bin2hex;
use function random_bytes;

/**
 * @internal
 */
#[CoversClass(AbstractSourceParameters::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(DeferredSourceDefinitionsMutable::class)]
#[CoversClass(DeferredSourceParameters::class)]
#[CoversClass(ImmediateSourceParameters::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(SourceParameterItem::class)]
#[CoversClass(ContainerParametersEntry::class)]
#[CoversClass(CompiledEntries::class)]
#[CoversClass(CompiledEntry::class)]
#[CoversClass(ContainerCompiler::class)]
#[CoversClass(ContainerCompilerToFile::class)]
#[CoversClass(DiContainerBuilder::class)]
#[CoversClass(DiContainerDefinitions::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerNullConfig::class)]
#[CoversClass(FinderClosureCode::class)]
#[CoversClass(DiDefinitionTransformer::class)]
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(IdsIterator::class)]
#[CoversClass(Helper::class)]
#[CoversClass(NotFoundException::class)]
#[CoversClass(ParameterNotFoundException::class)]
class SourceParametersOnCompiledContainerTest extends TestCase
{
    private DiContainerBuilder $containerBuilder;
    private string $containerClass;

    protected function setUp(): void
    {
        vfsStream::setup('root');

        $this->containerClass = 'App\Container'.bin2hex(random_bytes(5));

        $this->containerBuilder = (new DiContainerBuilder(
            new DiContainerNullConfig()
        ))
            ->compileToFile(
                vfsStream::url('root/'),
                $this->containerClass,
                isExclusiveLockFile: false,
            )
        ;
    }

    protected function tearDown(): void
    {
        unset($this->containerBuilder, $this->containerClass);
    }

    public function testCompiledContainerParametersEmpty(): void
    {
        $container = $this->containerBuilder->build();
        $params = $container->parameters();

        self::assertInstanceOf(ImmediateSourceParameters::class, $params);
        self::assertCount(0, [...$params->parameters()]);

        $params->set('foo', '{bar}');
        $params->set('bar', ['{baz}', true]);
        $params->set('baz', 100_000);

        self::assertEquals(
            [100_000, true],
            $container->parameters()->get('foo')
        );
    }

    public function testCompiledContainerParametersWithDynamicParameter(): void
    {
        $container = $this->containerBuilder
            ->addParameters([
                'foo' => '{bar}',
                'bar' => ['{baz}', '{number}'],
                'baz' => true,
                'number' => 100_000,
            ])
            ->build()
        ;

        $params = $container->parameters();

        self::assertInstanceOf(DeferredSourceParameters::class, $params);
        self::assertCount(4, [...$params->parameters()]);

        $params->set('qux', '{foo}');

        self::assertEquals(
            [true, 100_000],
            $params->get('qux')
        );

        self::assertEquals(
            [true, 100_000],
            $params->get('foo')
        );

        self::assertTrue($params->has('baz'));
        self::assertEquals(100_000, $params->has('number'));
    }

    public function testCompileInvalidBehaviorOnCompile(): void
    {
        $this->expectException(ContainerBuilderExceptionInterface::class);
        $this->expectExceptionMessage('compile container parameters.');

        $this->containerBuilder->compileToFile(
            vfsStream::url('root/'),
            $this->containerClass,
            isExclusiveLockFile: false,
            options: [
                'invalid_behavior' => InvalidBehaviorCompileEnum::ExceptionOnCompile,
            ],
        )
            ->addParameters([
                'foo' => '{bar}',
                'bar' => 'Lorem ipsum',
                'baz' => '{qux}',
            ])
            ->build()
        ;
    }

    public function testCompileInvalidBehaviorOnRuntimeParameterNotFound(): void
    {
        $container = $this->containerBuilder->compileToFile(
            vfsStream::url('root/'),
            $this->containerClass,
            isExclusiveLockFile: false,
            options: [
                'invalid_behavior' => InvalidBehaviorCompileEnum::RuntimeContainerException,
            ],
        )
            ->addParameters([
                'foo' => '{bar}',
                'bar' => 'Lorem ipsum',
                'baz' => '{qux}',
            ])
            ->build()
        ;

        $params = $container->parameters();

        self::assertInstanceOf(DeferredSourceParameters::class, $params);

        self::assertEquals('Lorem ipsum', $params->get('foo'));
        self::assertEquals('Lorem ipsum', $params->get('bar'));
        self::assertTrue($params->has('baz'));
        self::assertFalse($params->has('qux'));

        $this->expectException(ParameterNotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Parameter name "qux" not found');

        $params->get('baz');
    }

    public function testCompileInvalidBehaviorOnRuntimeParameterWrongType(): void
    {
        $container = $this->containerBuilder->compileToFile(
            vfsStream::url('root/'),
            $this->containerClass,
            isExclusiveLockFile: false,
            options: [
                'invalid_behavior' => InvalidBehaviorCompileEnum::RuntimeContainerException,
            ],
        )
            ->addParameters([
                'foo' => '{bar}',
                'bar' => 'Lorem ipsum',
                'baz' => new stdClass(),
            ])
            ->build()
        ;

        $params = $container->parameters();

        self::assertInstanceOf(DeferredSourceParameters::class, $params);

        self::assertEquals('Lorem ipsum', $params->get('foo'));
        self::assertEquals('Lorem ipsum', $params->get('bar'));
        self::assertTrue($params->has('baz'));

        $this->expectException(ParameterExceptionInterface::class);
        $this->expectExceptionMessage('the parameter "baz" has unsupported value type: "stdClass".');

        $params->get('baz');
    }
}
