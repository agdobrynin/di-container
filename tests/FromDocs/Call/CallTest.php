<?php

declare(strict_types=1);

namespace Tests\FromDocs\Call;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\DefinitionDiCall;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Reflection\ReflectionMethodByDefinition;
use Kaspi\DiContainer\SourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\Call\Fixtires\ServiceOne;

use function array_filter;

use const ARRAY_FILTER_USE_BOTH;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(DefinitionDiCall::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(Helper::class)]
#[CoversClass(ReflectionMethodByDefinition::class)]
#[CoversClass(SourceDefinitionsMutable::class)]
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
