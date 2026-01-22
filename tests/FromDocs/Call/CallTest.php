<?php

declare(strict_types=1);

namespace Tests\FromDocs\Call;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\Call\Fixtires\ServiceOne;

use function array_filter;

use const ARRAY_FILTER_USE_BOTH;

/**
 * @internal
 */
#[CoversNothing]
class CallTest extends TestCase
{
    public function testController(): void
    {
        $_POST = ['name' => 'Ivan'];

        $container = (new DiContainerBuilder())->build();

        $res = $container->call(
            ['\Tests\FromDocs\Call\Fixtires\PostController', 'store'],
            // $_POST содержит ['name' => 'Ivan']
            ...array_filter($_POST, static fn ($v, $k) => 'name' === $k, ARRAY_FILTER_USE_BOTH)
        );

        self::assertEquals('The name Ivan saved!', $res);
    }

    public function testCallbackFunction(): void
    {
        $container = (new DiContainerBuilder())->build();

        // определение callback с типизированным параметром
        $helperOne = static function (ServiceOne $service, string $name): string {
            $service->save($name);

            return 'The name '.$name.' saved!';
        };

        self::assertEquals('The name Vasiliy saved!', $container->call($helperOne, ...['name' => 'Vasiliy']));
    }
}
