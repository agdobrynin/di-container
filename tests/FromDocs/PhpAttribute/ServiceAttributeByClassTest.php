<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\CustomLogger;
use Tests\FromDocs\PhpAttribute\Fixtures\MyLogger;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\diAutowire
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
class ServiceAttributeByClassTest extends TestCase
{
    public function testResolveByServiceWithClass(): void
    {
        $definitions = [
            diAutowire(CustomLogger::class)
                ->bindArguments(file: '/var/log/app.log'),
        ];

        $container = (new DiContainerFactory())->make($definitions);

        $myClass = $container->get(MyLogger::class);

        $this->assertEquals('/var/log/app.log', $myClass->customLogger->loggerFile());
    }
}
