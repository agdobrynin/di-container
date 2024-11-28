<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diReference;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionReference
 * @covers \Kaspi\DiContainer\diReference
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class DiReferenceTest extends TestCase
{
    public function dataProvider(): \Generator
    {
        yield 'env APP_TEST_FILE=prod' => ['prod', 'file1.txt'];

        yield 'env APP_TEST_FILE=dev' => ['dev', 'file2.txt'];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDiReference(string $envValue, string $expectFile): void
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
                ->addArgument('filename', diReference('services.env')), // ссылка на определение
        ];

        $container = (new DiContainerFactory())->make($definitions);

        $this->assertEquals($expectFile, $container->get(\SplFileInfo::class)->getFilename());
    }
}
