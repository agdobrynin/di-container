<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader;

use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\DefinitionsLoader
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 *
 * @internal
 */
class DefinitionsLoaderTest extends TestCase
{
    public function dataProviderInvalidContent(): \Generator
    {
        yield 'no return' => [__DIR__.'/Fixtures/FailContent/f1.php'];

        yield 'none php' => [__DIR__.'/Fixtures/FailContent/f2.txt'];
    }

    /**
     * @dataProvider dataProviderInvalidContent
     */
    public function testInvalidFileContent(string $file): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('return not valid format');

        (new DefinitionsLoader())->load(false, $file);
    }

    public function testFileNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist or is not readable');

        (new DefinitionsLoader())->load(false, 'f.php');
    }

    public function dataProvideDefinitionException(): \Generator
    {
        yield 'definition without container identifier' => [__DIR__.'/Fixtures/DefinitionException/no-identifier.php'];

        yield 'definition empty identifier' => [__DIR__.'/Fixtures/DefinitionException/empty-identifier.php'];
    }

    /**
     * @dataProvider dataProvideDefinitionException
     */
    public function testDefinitionException(string $file): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Invalid definition in file "'.$file.'".');

        (new DefinitionsLoader())->load(false, $file);
    }

    public function testOverrideDefinitionException(): void
    {
        $this->expectException(ContainerAlreadyRegisteredException::class);

        (new DefinitionsLoader())->load(
            false,
            __DIR__.'/Fixtures/config1.php',
            __DIR__.'/Fixtures/config2.php',
        );
    }

    public function testOverrideDefinition(): void
    {
        $loader = (new DefinitionsLoader())->load(
            true,
            __DIR__.'/Fixtures/config1.php',
            __DIR__.'/Fixtures/config2.php',
        );

        $this->assertEquals('ok2', $loader->definitions()->current()());
    }
}
