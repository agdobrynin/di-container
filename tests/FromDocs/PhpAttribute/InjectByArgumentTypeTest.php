<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Tests\FromDocs\PhpAttribute\Fixtures\MyFile;

use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversNothing]
class InjectByArgumentTypeTest extends TestCase
{
    public function testInjectByArgumentType(): void
    {
        $definitions = static function () {
            yield diAutowire(SplFileInfo::class)
                ->bindArguments(filename: __DIR__.'/Fixtures/file1.txt')
            ;
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($definitions())
            ->build()
        ;
        // Получение данных из контейнера с автоматическим связыванием зависимостей
        $myClass = $container->get(MyFile::class);

        self::assertEquals('file1.txt', $myClass->fileInfo->getFilename());
    }
}
