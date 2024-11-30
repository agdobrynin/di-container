<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\MyFileByContainerIdentifier;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class InjectByContainerIdentifierTest extends TestCase
{
    public function dataProvider(): \Generator
    {
        yield 'production env' => ['prod', 'file1.txt'];

        yield 'local env' => ['local', 'file2.txt'];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testByContainerIdentifier(string $env, string $fileName): void
    {
        $configFromFiles = (static function (string ...$file): \Generator {
            foreach ($file as $srcFile) {
                $src = require $srcFile;

                if ($src instanceof \Closure) {
                    yield from $src();
                } elseif (\is_array($src)) {
                    yield from $src;
                } else {
                    throw new \InvalidArgumentException('Unexpected value from file: '.$srcFile);
                }
            }
        });

        $fileList = \glob(__DIR__.'/Fixtures/config/*.php');
        $container = (new DiContainerFactory())->make($configFromFiles(...$fileList));

        \putenv('APP_TEST_FILE');
        \putenv('APP_TEST_FILE='.$env);

        $class = $container->get(MyFileByContainerIdentifier::class);

        $this->assertEquals($fileName, $class->fileInfo->getFilename());
    }
}
