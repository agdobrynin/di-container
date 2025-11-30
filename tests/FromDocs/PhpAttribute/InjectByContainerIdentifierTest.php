<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Generator;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\MyFileByContainerIdentifier;

use function glob;
use function putenv;

/**
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\DefinitionsLoader
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\Helper
 *
 * @internal
 */
class InjectByContainerIdentifierTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testByContainerIdentifier(string $env, string $fileName): void
    {
        $definitions = (new DefinitionsLoader())
            ->load(...glob(__DIR__.'/Fixtures/config/*.php'))
        ;

        $container = (new DiContainerFactory())->make($definitions->definitions());

        putenv('APP_TEST_FILE');
        putenv('APP_TEST_FILE='.$env);

        $class = $container->get(MyFileByContainerIdentifier::class);

        $this->assertEquals($fileName, $class->fileInfo->getFilename());
    }

    public function dataProvider(): Generator
    {
        yield 'production env' => ['prod', 'file1.txt'];

        yield 'local env' => ['local', 'file2.txt'];
    }
}
