<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Generator;
use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
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
#[CoversNothing]
class DiReferenceTest extends TestCase
{
    #[DataProvider('dataProvider')]
    public function testDiGet(string $envValue, string $expectFile): void
    {
        putenv('APP_TEST_FILE');
        putenv('APP_TEST_FILE='.$envValue);

        $definitions = static function () {
            yield 'services.env' => diCallable(
                definition: static function () {
                    return match (getenv('APP_TEST_FILE')) {
                        'prod' => __DIR__.'/Fixtures/file1.txt',
                        default => __DIR__.'/Fixtures/file2.txt',
                    };
                },
                isSingleton: true
            );

            yield diAutowire(SplFileInfo::class)
                ->bindArguments(filename: diGet('services.env')) // ссылка на определение
            ;
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($definitions())
            ->build()
        ;

        self::assertEquals($expectFile, $container->get(SplFileInfo::class)->getFilename());
    }

    public static function dataProvider(): Generator
    {
        yield 'env APP_TEST_FILE=prod' => ['prod', 'file1.txt'];

        yield 'env APP_TEST_FILE=dev' => ['dev', 'file2.txt'];
    }

    public function testDiGetByClasses(): void
    {
        $definitions = static function () {
            yield 'data' => ['user1', 'user2'];

            // ... more definitions

            // внедрение зависимости аргумента по ссылке на контейнер-id
            yield diAutowire(MyUsers::class)
                // unsorted bind by name
                ->bindArguments(
                    type: 'Some value',
                    users: diGet('data')
                )
            ;

            yield diAutowire(MyEmployers::class)
                // bind by index
                ->bindArguments(
                    diGet('data'),
                    'Other value',
                )
            ;
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($definitions())
            ->build()
        ;

        $users = $container->get(MyUsers::class);

        self::assertEquals(['user1', 'user2'], $users->users);
        self::assertEquals('Some value', $users->type);

        $employers = $container->get(MyEmployers::class);

        self::assertEquals(['user1', 'user2'], $employers->employers);
        self::assertEquals('Other value', $employers->type);
    }
}
