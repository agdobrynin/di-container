<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\MyFileByContainerIdentifier;

use function glob;
use function putenv;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversFunction('\Kaspi\DiContainer\diCallable')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(Inject::class)]
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(Helper::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
class InjectByContainerIdentifierTest extends TestCase
{
    #[DataProvider('dataProvider')]
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

    public static function dataProvider(): Generator
    {
        yield 'production env' => ['prod', 'file1.txt'];

        yield 'local env' => ['local', 'file2.txt'];
    }
}
