<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\MyEmployers;
use Tests\FromDocs\PhpDefinitions\Fixtures\MyUsers;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diGet;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class diGetTest extends TestCase
{
    public function dataProvider(): \Generator
    {
        yield 'env APP_TEST_FILE=prod' => ['prod', 'file1.txt'];

        yield 'env APP_TEST_FILE=dev' => ['dev', 'file2.txt'];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testdiGet(string $envValue, string $expectFile): void
    {
        \putenv('APP_TEST_FILE');
        \putenv('APP_TEST_FILE='.$envValue);

        $definitions = [
            'services.env' => diCallable(
                definition: static function () {
                    return match (\getenv('APP_TEST_FILE')) {
                        'prod' => __DIR__.'/Fixtures/file1.txt',
                        default => __DIR__.'/Fixtures/file2.txt',
                    };
                },
                isSingleton: true
            ),
            diAutowire(\SplFileInfo::class)
                ->addArgument('filename', diGet('services.env')), // ссылка на определение
        ];

        $container = (new DiContainerFactory())->make($definitions);

        $this->assertEquals($expectFile, $container->get(\SplFileInfo::class)->getFilename());
    }

    public function testdiGetByClasses(): void
    {
        $definitions = [
            'data' => ['user1', 'user2'],

            // ... more definitions

            // внедрение зависимости аргумента по ссылке на контейнер-id
            diAutowire(MyUsers::class)
                ->addArgument('users', diGet('data'))
                ->addArgument('type', 'Some value'),
            diAutowire(MyEmployers::class)
                // добавить много аргументов за один раз
                ->addArguments([
                    'employers' => diGet('data'),
                    'type' => 'Other value',
                ]),
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
