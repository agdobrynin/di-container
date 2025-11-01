<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\FromDocs\PhpDefinitions\Fixtures\ServiceLocation;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\functionName
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
class ResolveByArgumentNameTest extends TestCase
{
    public function testResolveByArgumentNameFail(): void
    {
        $definitions = [
            'locationCity' => 'Vice city',
        ];

        $container = (new DiContainerFactory())->make($definitions);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot automatically resolve dependency.+ServiceLocation::__construct\(\).+string \$locationCity/');

        $container->get(ServiceLocation::class);
    }
}
