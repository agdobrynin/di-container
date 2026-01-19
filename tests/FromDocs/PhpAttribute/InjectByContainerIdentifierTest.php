<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Generator;
use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\MyFileByContainerIdentifier;

use function glob;
use function putenv;

/**
 * @internal
 */
#[CoversNothing]
class InjectByContainerIdentifierTest extends TestCase
{
    #[DataProvider('dataProvider')]
    public function testByContainerIdentifier(string $env, string $fileName): void
    {
        $container = (new DiContainerBuilder())
            ->load(...glob(__DIR__.'/Fixtures/config/*.php'))
            ->build()
        ;

        putenv('APP_TEST_FILE');
        putenv('APP_TEST_FILE='.$env);

        $class = $container->get(MyFileByContainerIdentifier::class);

        self::assertEquals($fileName, $class->fileInfo->getFilename());
    }

    public static function dataProvider(): Generator
    {
        yield 'production env' => ['prod', 'file1.txt'];

        yield 'local env' => ['local', 'file2.txt'];
    }
}
