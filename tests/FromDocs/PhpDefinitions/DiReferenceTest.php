<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\SourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Tests\FromDocs\PhpDefinitions\Fixtures\MyEmployers;
use Tests\FromDocs\PhpDefinitions\Fixtures\MyUsers;

use function getenv;
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diGet;
use function putenv;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversFunction('\Kaspi\DiContainer\diCallable')]
#[CoversFunction('\Kaspi\DiContainer\diGet')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(Helper::class)]
#[CoversClass(SourceDefinitionsMutable::class)]
class DiReferenceTest extends TestCase
{
    #[DataProvider('dataProvider')]
    public function testdiGet(string $envValue, string $expectFile): void
    {
        putenv('APP_TEST_FILE');
        putenv('APP_TEST_FILE='.$envValue);

        $definitions = [
            'services.env' => diCallable(
                definition: static function () {
                    return match (getenv('APP_TEST_FILE')) {
                        'prod' => __DIR__.'/Fixtures/file1.txt',
                        default => __DIR__.'/Fixtures/file2.txt',
                    };
                },
                isSingleton: true
            ),
            diAutowire(SplFileInfo::class)
                ->bindArguments(filename: diGet('services.env')), // ссылка на определение
        ];

        $container = (new DiContainerFactory())->make($definitions);

        $this->assertEquals($expectFile, $container->get(SplFileInfo::class)->getFilename());
    }

    public static function dataProvider(): Generator
    {
        yield 'env APP_TEST_FILE=prod' => ['prod', 'file1.txt'];

        yield 'env APP_TEST_FILE=dev' => ['dev', 'file2.txt'];
    }

    public function testdiGetByClasses(): void
    {
        $definitions = [
            'data' => ['user1', 'user2'],

            // ... more definitions

            // внедрение зависимости аргумента по ссылке на контейнер-id
            diAutowire(MyUsers::class)
                // unsorted bind by name
                ->bindArguments(
                    type: 'Some value',
                    users: diGet('data')
                ),
            diAutowire(MyEmployers::class)
                // bind by index
                ->bindArguments(
                    diGet('data'),
                    'Other value',
                ),
        ];

        $container = (new DiContainerFactory())->make($definitions);

        $users = $container->get(MyUsers::class);
        $this->assertEquals(['user1', 'user2'], $users->users);
        $this->assertEquals('Some value', $users->type);

        $employers = $container->get(MyEmployers::class);
        $this->assertEquals(['user1', 'user2'], $employers->employers);
        $this->assertEquals('Other value', $employers->type);
    }
}
