<?php

declare(strict_types=1);

namespace Tests\Compiler\ContainerCompilerToFile;

use Generator;
use Kaspi\DiContainer\Compiler\ContainerCompilerToFile;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledContainerFQNInterface;
use Kaspi\DiContainer\Interfaces\Compiler\ContainerCompilerInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function file_exists;
use function file_get_contents;
use function md5;
use function microtime;
use function str_shuffle;
use function substr;
use function uniqid;

/**
 * @internal
 */
#[CoversClass(ContainerCompilerToFile::class)]
class ContainerCompilerToFileTest extends TestCase
{
    private string $tmpContainerClassSuffix;
    private ContainerCompilerInterface $compiler;

    protected function setUp(): void
    {
        $this->tmpContainerClassSuffix = substr(str_shuffle(md5(microtime())), 0, 10);
        $this->compiler = $this->createMock(ContainerCompilerInterface::class);
        $this->compiler->method('getContainerFQN')->willReturn(
            new class($this->tmpContainerClassSuffix) implements CompiledContainerFQNInterface {
                public function __construct(private readonly string $containerClassSuffix) {}

                public function getNamespace(): string
                {
                    return '';
                }

                public function getClass(): string
                {
                    return 'Container'.$this->containerClassSuffix;
                }

                public function getFQN(): string
                {
                    return '\Container'.$this->containerClassSuffix;
                }
            }
        );
    }

    public function tearDown(): void
    {
        unset($this->compiler, $this->tmpContainerClassSuffix);
    }

    #[DataProvider('dataProviderFailOutputDirectory')]
    public function testFailOutputDirectory(string $dir, string $expectMessage): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectMessage);

        (new ContainerCompilerToFile($dir, $this->compiler))->getOutputDirectory();
    }

    public static function dataProviderFailOutputDirectory(): Generator
    {
        yield 'directory as file' => [
            __FILE__,
            'Compiler output directory from parameter $outputDirectory must be exist. Got argument "'.__FILE__.'".',
        ];

        $suffix = uniqid('dir', true);

        yield 'directory not exist' => [
            '/Not/Exist/Directory/'.$suffix,
            'Compiler output directory from parameter $outputDirectory must be exist. Got argument "/Not/Exist/Directory/'.$suffix.'".',
        ];
    }

    public function testFailPermissionOutputDirectory(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Compiler output directory must be be readable.');

        $root = vfsStream::setup();
        $dir = vfsStream::newDirectory('dir', 0222)
            ->at($root)
        ;

        (new ContainerCompilerToFile($dir->url(), $this->compiler))->getOutputDirectory();
    }

    public function testFailPermissionOutputDirectoryWhenCompile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Compiler output directory must be be writable.');

        $root = vfsStream::setup();
        $dir = vfsStream::newDirectory('dir', 0444)
            ->at($root)
        ;

        (new ContainerCompilerToFile($dir->url(), $this->compiler))->compileToFile();
    }

    public function testSuccessOutputDirectory(): void
    {
        $root = vfsStream::setup();
        $dir = vfsStream::newDirectory('dir')
            ->at($root)
        ;

        $dirResult = (new ContainerCompilerToFile($dir->url(), $this->compiler))->getOutputDirectory();

        self::assertDirectoryExists($dirResult);
    }

    public function testCompileToFileCompiledFileExists(): void
    {
        vfsStream::setup(structure: [
            'Container'.$this->tmpContainerClassSuffix.'.php' => 'some content',
            'pass' => 'secure info',
        ]);

        $file = (new ContainerCompilerToFile(vfsStream::url('root//'), $this->compiler))
            ->compileToFile()
        ;

        self::assertStringEndsWith('root/Container'.$this->tmpContainerClassSuffix.'.php', $file);
    }

    public function testFailCompileToFileOutOfFreeDiskSpaceAndRestoreExistFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('possibly out of free disk space');

        $existContainerFile = 'Container'.$this->tmpContainerClassSuffix.'.php';

        vfsStream::setup(structure: [
            $existContainerFile => 'old content',
        ]);
        vfsStream::setQuota(11);

        $this->compiler->method('compile')
            ->willReturn('new long content with length over disk space')
        ;

        (new ContainerCompilerToFile(vfsStream::url('root'), $this->compiler, isExclusiveLockFile: false))
            ->compileToFile(true)
        ;

        $urlContainerFile = vfsStream::url('root/'.$existContainerFile);

        self::assertTrue(file_exists($urlContainerFile)); // Old file restored
        self::assertEquals('old content', file_get_contents($urlContainerFile));
    }

    public function testSuccessCompileToFile(): void
    {
        vfsStream::setup();

        $this->compiler->method('compile')
            ->willReturn('some content')
        ;

        $file = (new ContainerCompilerToFile(vfsStream::url('root'), $this->compiler, isExclusiveLockFile: false))
            ->compileToFile()
        ;

        self::assertEquals(vfsStream::url('root/Container'.$this->tmpContainerClassSuffix.'.php'), $file);
        self::assertEquals('some content', file_get_contents($file));
    }
}
