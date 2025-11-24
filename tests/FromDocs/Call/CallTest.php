<?php

declare(strict_types=1);

namespace Tests\FromDocs\Call;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\Call\Fixtires\ServiceOne;

use function array_filter;

use const ARRAY_FILTER_USE_BOTH;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\Helper
 * @covers \Kaspi\DiContainer\Reflection\ReflectionMethodByDefinition
 *
 * @internal
 */
class CallTest extends TestCase
{
    public function testController(): void
    {
        $_POST = ['name' => 'Ivan'];

        // try call
        $container = (new DiContainerFactory())->make();

        $res = $container->call(
            ['\Tests\FromDocs\Call\Fixtires\PostController', 'store'],
            // $_POST содержит ['name' => 'Ivan']
            array_filter($_POST, static fn ($v, $k) => 'name' === $k, ARRAY_FILTER_USE_BOTH)
        );

        $this->assertEquals('The name Ivan saved!', $res);
    }

    public function testCallbackFunction(): void
    {
        $container = (new DiContainerFactory())->make();

        // определение callback с типизированным параметром
        $helperOne = static function (ServiceOne $service, string $name): string {
            $service->save($name);

            return 'The name '.$name.' saved!';
        };

        $this->assertEquals('The name Vasiliy saved!', $container->call($helperOne, ['name' => 'Vasiliy']));
    }
}
