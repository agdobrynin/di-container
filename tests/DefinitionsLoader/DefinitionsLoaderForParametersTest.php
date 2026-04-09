<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader;

use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DefinitionsLoader::class)]
class DefinitionsLoaderForParametersTest extends TestCase
{
    private $root;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('/');
    }

    protected function tearDown(): void
    {
        unset($this->root);
    }

    public function testCannotLoadFromNoneExistFile(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);

        $loader = new DefinitionsLoader();
        $loader->loadParameters('/none_exist_file.php');
    }

    public function testCannotLoadFromNoneReadableFile(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);

        vfsStream::newFile('params.php', 0222)
            ->setContent('<?php return ["foo" => "bar"];')
            ->at($this->root)
        ;

        $loader = new DefinitionsLoader();
        $loader->loadParameters(vfsStream::url('/params.php'));
    }

    public function testCannotLoadFromParseErrorFile(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('syntax error');

        vfsStream::newFile('params.php')
            ->setContent('<?php return "foo" => "bar"')
            ->at($this->root)
        ;

        $loader = new DefinitionsLoader();
        $loader->loadParameters(vfsStream::url('/params.php'));
    }

    #[TestWith(['some text'])]
    #[TestWith(['<?php ["foo" => "bar"];'])]
    #[TestWith(['<?php return static function () { ["foo" => "bar"]; };'])]
    public function testCannotLoadFromFileNoneIterableContent(string $content): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('must be use "return" keyword and return any iterable type');

        vfsStream::newFile('params.php')
            ->setContent($content)
            ->at($this->root)
        ;

        $loader = new DefinitionsLoader();
        $loader->loadParameters(vfsStream::url('/params.php'));
    }

    #[TestWith(['<?php return ["foo" => "bar"];', ['foo' => 'bar']])]
    #[TestWith(['<?php return new ArrayIterator(["foo" => "bar"]);', ['foo' => 'bar']])]
    #[TestWith(['<?php return static function () { return ["foo" => "bar"]; };', ['foo' => 'bar']])]
    #[TestWith(['<?php return static function () { yield "foo" => "bar"; yield "baz" => "bat"; };', ['foo' => 'bar', 'baz' => 'bat']])]
    public function testLoadParametersSuccess(string $content, array $expect): void
    {
        vfsStream::newFile('params.php')
            ->setContent($content)
            ->at($this->root)
        ;

        $loader = (new DefinitionsLoader())
            ->loadParameters(vfsStream::url('/params.php'))
        ;

        self::assertEquals($expect, [...$loader->parameters()]);
    }

    public function testLoadParametersFromTwoFile(): void
    {
        vfsStream::newFile('params1.php')
            ->setContent('<?php return ["foo" => "bar"];')
            ->at($this->root)
        ;

        vfsStream::newFile('params2.php')
            ->setContent('<?php return ["baz" => "qux"];')
            ->at($this->root)
        ;

        $loader = (new DefinitionsLoader())
            ->loadParameters(vfsStream::url('/params1.php'), vfsStream::url('/params2.php'))
        ;

        self::assertEquals(['foo' => 'bar', 'baz' => 'qux'], [...$loader->parameters()]);
    }

    public function testReset(): void
    {
        vfsStream::newFile('params.php')
            ->setContent('<?php return ["foo" => "bar"];')
            ->at($this->root)
        ;

        $loader = (new DefinitionsLoader())
            ->loadParameters(vfsStream::url('/params.php'))
        ;

        self::assertEquals(['foo' => 'bar'], [...$loader->parameters()]);

        $loader->reset();

        self::assertEquals([], [...$loader->parameters()]);
    }
}
