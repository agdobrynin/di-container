<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Tests\FromDocs\PhpAttribute\Fixtures\MyFile;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
class InjectByArgumentTypeTest extends TestCase
{
    public function testInjectByArgumentType(): void
    {
        $definitions = [
            diAutowire(SplFileInfo::class)
                ->bindArguments(filename: __DIR__.'/Fixtures/file1.txt'),
        ];

        $container = (new DiContainerFactory())->make($definitions);
        // Получение данных из контейнера с автоматическим связыванием зависимостей
        $myClass = $container->get(MyFile::class);

        $this->assertEquals('file1.txt', $myClass->fileInfo->getFilename());
    }
}
